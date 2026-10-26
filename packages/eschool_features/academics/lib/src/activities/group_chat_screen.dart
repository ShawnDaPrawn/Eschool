import 'dart:async';
import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:uuid/uuid.dart';
import '../content/content_widgets.dart';
import 'group_chat_repository.dart';

/// Embedded in the existing group route, so the app's generation boundary also
/// retires the conversation. Nothing is stored in a global or durable cache.
class GroupChatScreen extends StatefulWidget {
  const GroupChatScreen({
    required this.repository,
    required this.onClose,
    this.onlineCount,
    super.key,
  });
  final GroupChatRepository repository;
  final VoidCallback onClose;
  final int? onlineCount;
  @override
  State<GroupChatScreen> createState() => _GroupChatScreenState();
}

class _GroupChatScreenState extends State<GroupChatScreen>
    with WidgetsBindingObserver {
  final _body = TextEditingController(),
      _title = TextEditingController(),
      _reason = TextEditingController();
  final _scroll = ScrollController();
  GroupChatThreadPage? _page;
  GroupChatConversation? _conversation;
  GroupChatMessage? _reply, _removing;
  ChatWriteIntent? _pending;
  String? _pendingKind, _pendingThread, _outcome, _error;
  int? _pendingMessage;
  int _epoch = 0;
  bool _loading = true,
      _hidden = false,
      _denied = false,
      _compose = false,
      _sending = false,
      _shared = false,
      _changed = false,
      _pendingUncertain = false;
  String _audience = 'learners', _language = 'en';
  bool _languageChosen = false;
  StreamSubscription<LiveInvalidation>? _events;
  StreamSubscription<void>? _refreshes;
  StreamSubscription<int>? _denials;
  Stream<LiveInvalidation>? _source;
  String l(String value) => contentLabel(context, value);
  bool get active =>
      mounted && !_hidden && !_denied && widget.repository.isActive;
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _load();
  }

  @override
  void didUpdateWidget(covariant GroupChatScreen oldWidget) {
    super.didUpdateWidget(oldWidget);
    final replacement = !identical(oldWidget.repository, widget.repository);
    if (replacement || !widget.repository.isActive) {
      _epoch++;
      _erase();
      _outcome = null;
      _loading = false;
      _sending = false;
      _denied = !replacement || !widget.repository.isActive;
      if (!_denied) _load();
    }
  }

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    if (!_languageChosen) {
      final locale = Localizations.localeOf(context).languageCode;
      _language = const ['en', 'af', 'zu', 'ar'].contains(locale)
          ? locale
          : 'en';
      _languageChosen = true;
    }
    final events = LiveUpdatesScope.eventsOf(context);
    if (identical(events, _source)) return;
    _source = events;
    _events?.cancel();
    _refreshes?.cancel();
    _denials?.cancel();
    void refresh() {
      if (!active) return;
      if (_compose ||
          _body.text.isNotEmpty ||
          _reply != null ||
          _removing != null ||
          _pending != null ||
          _sending ||
          _loading) {
        setState(() => _changed = true);
      } else {
        _load(thread: _conversation?.thread.id);
      }
    }

    _events = events.listen((event) {
      if (event.type == 'activity.group_changed' &&
          event.schoolId == widget.repository.schoolId &&
          event.groupId == widget.repository.groupId)
        refresh();
    });
    _refreshes = LiveUpdatesScope.refreshesOf(context).listen((_) => refresh());
    _denials = LiveUpdatesScope.sessionOf(context)?.deniedGroups.listen((
      group,
    ) {
      if (group == widget.repository.groupId && mounted) _retire(denied: true);
    });
  }

  void _erase() {
    _body.clear();
    _title.clear();
    _reason.clear();
    _reply = null;
    _removing = null;
    _pending?.erase();
    _pending = null;
    _pendingKind = null;
    _pendingThread = null;
    _pendingMessage = null;
    _pendingUncertain = false;
    _compose = false;
    _shared = false;
    _conversation = null;
    _page = null;
  }

  void _retire({bool denied = false}) {
    _epoch++;
    final operation = _pending?.operationId;
    setState(() {
      _erase();
      _outcome = denied ? null : operation ?? _outcome;
      _loading = false;
      _sending = false;
      _changed = false;
      _denied = denied;
    });
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state != AppLifecycleState.resumed) {
      _hidden = true;
      _retire();
    } else {
      _hidden = false;
      if (widget.repository.isActive && !_denied)
        _load();
      else
        _retire(denied: true);
    }
  }

  void _showStatus() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (active && _scroll.hasClients)
        _scroll.animateTo(
          0,
          duration: const Duration(milliseconds: 160),
          curve: Curves.easeOut,
        );
    });
  }

  void _failure(Object error) {
    final status = error is ApiException ? error.statusCode : null;
    if (!widget.repository.isActive ||
        status == 401 ||
        status == 403 ||
        status == 404) {
      _retire(denied: true);
      return;
    }
    setState(
      () => _error = switch (status) {
        409 => 'The conversation changed. Refresh it before trying again.',
        422 => 'Review the message and the current character limit.',
        423 => 'This conversation is read only.',
        _ =>
          'The conversation could not be confirmed. Refresh access or retry the same change.',
      },
    );
    _showStatus();
  }

  Future<void> _load({String? thread, bool older = false}) async {
    if (!active) return;
    final epoch = ++_epoch;
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final page = thread == null
          ? await widget.repository.threads(
              before: older ? _page?.nextBefore : null,
            )
          : null;
      final conversation = thread == null
          ? null
          : await widget.repository.read(
              thread,
              before: older ? _conversation?.nextBefore : null,
            );
      if (!active || epoch != _epoch) return;
      setState(() {
        if (page != null) _page = page;
        _conversation = conversation;
        _loading = false;
        _changed = false;
      });
      if (conversation != null && conversation.items.isNotEmpty) {
        // Mark only the actually visible fresh response. Read markers carry no body.
        await widget.repository.markRead(thread!, conversation.items.last.id);
        if (!active || epoch != _epoch) return;
      }
    } catch (error) {
      if (!active || epoch != _epoch) return;
      setState(() => _loading = false);
      _failure(error);
    }
  }

  Future<void> _write(
    String kind,
    Map<String, dynamic> fields, {
    String? thread,
    int? message,
  }) async {
    if (!active || _sending || _outcome != null) return;
    FocusManager.instance.primaryFocus?.unfocus();
    _pending ??= ChatWriteIntent(const Uuid().v4(), fields);
    _pendingKind ??= kind;
    _pendingThread ??= thread;
    _pendingMessage ??= message;
    await _retry();
  }

  Future<void> _retry() async {
    final pending = _pending, kind = _pendingKind, thread = _pendingThread;
    if (!active || pending == null || kind == null || _sending) return;
    final epoch = _epoch;
    setState(() {
      _sending = true;
      _error = null;
    });
    try {
      final GroupChatMutation result;
      switch (kind) {
        case 'create':
          result = await widget.repository.create(pending);
        case 'send':
          result = await widget.repository.send(thread!, pending);
        case 'state':
          result = await widget.repository.state(thread!, pending);
        case 'remove':
          result = await widget.repository.remove(
            thread!,
            _pendingMessage!,
            pending,
          );
        default:
          throw StateError('Unknown conversation write.');
      }
      if (!active || epoch != _epoch || pending.erased) return;
      setState(() {
        _erase();
        _outcome = null;
        _sending = false;
      });
      await _load(thread: result.threadId);
    } catch (error) {
      if (!active || epoch != _epoch) return;
      setState(() => _sending = false);
      final status = error is ApiException ? error.statusCode : null;
      if (status == 409 || status == 422 || status == 423) {
        if (_pendingUncertain) {
          // A refusal of a retry cannot prove that the earlier request failed.
          // Retire text and preserve only its original outcome reference.
          _retire();
          await _load();
          return;
        }
        setState(() {
          _pending?.erase();
          _pending = null;
          _pendingKind = null;
          _pendingThread = null;
          _pendingMessage = null;
        });
      } else if (status == null || status >= 500 || status == 429) {
        _pendingUncertain = true;
      }
      _failure(error);
    }
  }

  Future<void> _checkOutcome() async {
    final operation = _outcome;
    if (!active || operation == null || _loading) return;
    final epoch = _epoch;
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final result = await widget.repository.receipt(operation);
      if (!active || epoch != _epoch) return;
      setState(() {
        _outcome = null;
        _loading = false;
      });
      await _load(thread: result.receipt.threadId);
    } catch (error) {
      if (!active || epoch != _epoch) return;
      setState(() => _loading = false);
      if (error is ApiException && error.statusCode == 404) {
        setState(
          () => _error =
              'The result is still unconfirmed. This screen will not resend the discarded draft.',
        );
      } else {
        _failure(error);
      }
    }
  }

  Future<void> _back() async {
    if (_sending) return;
    if (_body.text.isNotEmpty ||
        _title.text.isNotEmpty ||
        _reason.text.isNotEmpty ||
        _pending != null) {
      final discard = await showDialog<bool>(
        context: context,
        builder: (context) => AlertDialog(
          title: Text(l('Discard the private draft?')),
          content: Text(
            l(
              'Your last change may already have finished. You can still check it after discarding the draft.',
            ),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context, false),
              child: Text(l('Keep editing')),
            ),
            FilledButton(
              onPressed: () => Navigator.pop(context, true),
              child: Text(l('Discard draft')),
            ),
          ],
        ),
      );
      if (discard != true || !mounted) return;
    }
    final hadConversation = _conversation != null || _compose;
    _retire();
    if (hadConversation)
      _load();
    else
      widget.onClose();
  }

  void _remove(GroupChatMessage row) {
    setState(() {
      _removing = row;
      _reason.clear();
      _reply = null;
    });
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (active && _removing?.id == row.id && _scroll.hasClients)
        _scroll.animateTo(
          0,
          duration: const Duration(milliseconds: 160),
          curve: Curves.easeOut,
        );
    });
  }

  Widget _removalEditor() {
    final row = _removing!;
    final disabled = _pending != null || _sending || _loading;
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Text(
              l('Remove this message'),
              style: Theme.of(context).textTheme.titleMedium,
            ),
            Text(
              l('This reason stays private to authorized moderation records.'),
            ),
            TextField(
              controller: _reason,
              enabled: !disabled,
              maxLength: 2000,
              maxLines: 3,
              decoration: InputDecoration(
                labelText: l('Private moderation reason'),
              ),
            ),
            FilledButton(
              onPressed: disabled
                  ? null
                  : () {
                      final reason = _reason.text.trim();
                      if (reason.isNotEmpty)
                        _write(
                          'remove',
                          {'revision': row.revision, 'reason': reason},
                          thread: _conversation!.thread.id,
                          message: row.id,
                        );
                    },
              child: Text(l('Remove message')),
            ),
            TextButton(
              onPressed: disabled
                  ? null
                  : () => setState(() {
                      _removing = null;
                      _reason.clear();
                    }),
              child: Text(l('Cancel removal')),
            ),
          ],
        ),
      ),
    );
  }

  Widget _editor() {
    final staff = widget.repository.persona == 'staff';
    final policy = _conversation?.policy ?? _page?.policy;
    final sharing = widget.repository.persona == 'parent'
        ? policy?.guardianSharedReplies == true
        : policy?.learnerSharedReplies == true;
    final limit = _conversation?.maxCharacters ?? _page?.maxCharacters ?? 500;
    final disabled =
        _pending != null || _sending || _loading || _outcome != null;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        if (_compose) ...[
          TextField(
            controller: _title,
            enabled: !disabled,
            maxLength: 191,
            decoration: InputDecoration(labelText: l('Conversation title')),
          ),
          if (staff)
            DropdownButtonFormField<String>(
              value: _audience,
              isExpanded: true,
              decoration: InputDecoration(labelText: l('Audience')),
              items: [
                for (final name in ['learners', 'guardians', 'staff'])
                  if (name == 'learners'
                      ? policy?.staffLearners == true
                      : name == 'guardians'
                      ? policy?.staffGuardians == true
                      : policy?.staffStaff == true)
                    DropdownMenuItem(
                      value: name,
                      child: Text(
                        l(
                          name == 'learners'
                              ? 'Learners'
                              : name == 'guardians'
                              ? 'Guardians'
                              : 'Staff',
                        ),
                      ),
                    ),
              ],
              onChanged: disabled
                  ? null
                  : (value) => setState(() => _audience = value!),
            ),
        ],
        if (_reply != null)
          ListTile(
            contentPadding: EdgeInsets.zero,
            title: Text(l('Replying privately to this participant')),
            subtitle: Text(_reply!.senderName),
            trailing: IconButton(
              tooltip: l('Cancel reply'),
              onPressed: disabled ? null : () => setState(() => _reply = null),
              icon: const Icon(Icons.close),
            ),
          ),
        Text(
          l(
            staff
                ? (_reply != null
                      ? 'Only this participant and assigned staff can read this reply.'
                      : 'This message is visible to the selected thread audience.')
                : (_shared
                      ? 'Other current participants in this audience can read this reply.'
                      : 'Only you and assigned group staff can read this message.'),
          ),
        ),
        if (!staff && !_compose && sharing)
          SwitchListTile(
            contentPadding: EdgeInsets.zero,
            title: Text(l('Share with this group audience')),
            value: _shared,
            onChanged: disabled
                ? null
                : (value) => setState(() => _shared = value),
          ),
        DropdownButtonFormField<String>(
          value: _language,
          isExpanded: true,
          decoration: InputDecoration(labelText: l('Message language')),
          items: [
            for (final item in const {
              'en': 'English',
              'af': 'Afrikaans',
              'zu': 'isiZulu',
              'ar': 'Arabic',
            }.entries)
              DropdownMenuItem(value: item.key, child: Text(l(item.value))),
          ],
          onChanged: disabled
              ? null
              : (value) => setState(() => _language = value!),
        ),
        TextField(
          controller: _body,
          enabled: !disabled,
          maxLength: limit,
          minLines: 2,
          maxLines: 5,
          decoration: InputDecoration(labelText: l('Message')),
        ),
        FilledButton(
          onPressed: disabled
              ? null
              : () {
                  if (_body.text.trim().isEmpty ||
                      (_compose && _title.text.trim().isEmpty))
                    return;
                  _write(_compose ? 'create' : 'send', {
                    'body': _body.text.trim(),
                    'language': _language,
                    if (_compose) ...{
                      'title': _title.text.trim(),
                      'kind': staff ? 'announcement' : 'question',
                      if (staff) 'audience': _audience,
                    } else ...{
                      'visibility': staff
                          ? (_reply != null ? 'staff' : 'thread')
                          : (_shared ? 'thread' : 'staff'),
                      if (_reply != null) 'reply_to': _reply!.id,
                    },
                  }, thread: _conversation?.thread.id);
                },
          child: Text(l(_compose ? 'Open conversation' : 'Send message')),
        ),
      ],
    );
  }

  @override
  Widget build(BuildContext context) {
    final conversation = _conversation;
    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, _) {
        if (!didPop) _back();
      },
      child: Scaffold(
        appBar: AppBar(
          leading: IconButton(
            tooltip: l('Back to activity group'),
            onPressed: _sending ? null : _back,
            icon: const Icon(Icons.arrow_back),
          ),
          title: Text(l('Group conversations')),
          actions: [
            IconButton(
              tooltip: l('Refresh conversation access'),
              onPressed:
                  _loading ||
                      _sending ||
                      _pending != null ||
                      _compose ||
                      _body.text.isNotEmpty
                  ? null
                  : () => _load(thread: conversation?.thread.id),
              icon: const Icon(Icons.refresh),
            ),
          ],
        ),
        body: _hidden || _denied || !widget.repository.isActive
            ? Center(
                child: Text(
                  l('Reopen Activity groups to refresh private access.'),
                ),
              )
            : ListView(
                controller: _scroll,
                padding: const EdgeInsets.all(16),
                children: [
                  if (_loading || _sending) const LinearProgressIndicator(),
                  if (widget.onlineCount != null)
                    Text('${l('Online now')}: ${widget.onlineCount}'),
                  if (_error != null)
                    Padding(
                      padding: const EdgeInsets.symmetric(vertical: 12),
                      child: Text(l(_error!)),
                    ),
                  if (_changed)
                    Text(
                      l(
                        'The conversation changed. Refresh after finishing your draft.',
                      ),
                    ),
                  if (_outcome != null)
                    Card(
                      child: Padding(
                        padding: const EdgeInsets.all(16),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.stretch,
                          children: [
                            Text(
                              l(
                                'The private draft was discarded. Your last change may already have finished; check it before writing again.',
                              ),
                            ),
                            FilledButton(
                              onPressed: _loading ? null : _checkOutcome,
                              child: Text(l('Check previous change')),
                            ),
                            TextButton(
                              onPressed: _loading
                                  ? null
                                  : () => setState(() => _outcome = null),
                              child: Text(l('Dismiss this check')),
                            ),
                          ],
                        ),
                      ),
                    ),
                  if (_pending != null)
                    Card(
                      child: Padding(
                        padding: const EdgeInsets.all(16),
                        child: Column(
                          children: [
                            Text(
                              l(
                                'Your last change may have finished. Retry the same change to confirm it.',
                              ),
                            ),
                            FilledButton(
                              onPressed: _sending ? null : _retry,
                              child: Text(l('Retry the same change')),
                            ),
                          ],
                        ),
                      ),
                    ),
                  if (conversation != null) ...[
                    Text(
                      conversation.thread.title ?? l('Conversation'),
                      style: Theme.of(context).textTheme.titleLarge,
                    ),
                    Text(
                      l(
                        conversation.thread.private
                            ? 'Private question to assigned staff'
                            : 'Group audience conversation',
                      ),
                    ),
                    if (_removing != null) _removalEditor(),
                    if (conversation.thread.closed)
                      Text(l('This conversation is closed.')),
                    if (conversation.nextBefore != null)
                      TextButton(
                        onPressed: _loading || _pending != null
                            ? null
                            : () => _load(
                                thread: conversation.thread.id,
                                older: true,
                              ),
                        child: Text(l('Earlier messages')),
                      ),
                    for (final row in conversation.items)
                      Card(
                        child: Padding(
                          padding: const EdgeInsets.all(12),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                row.senderName,
                                style: Theme.of(context).textTheme.titleSmall,
                              ),
                              Text(
                                l(
                                  row.private
                                      ? 'Private to participant and staff'
                                      : 'Visible to this audience',
                                ),
                              ),
                              SelectableText(
                                row.removed
                                    ? l('Message removed')
                                    : row.body ?? '',
                              ),
                              Wrap(
                                spacing: 8,
                                children: [
                                  if (widget.repository.persona == 'staff' &&
                                      row.private &&
                                      row.senderPersona.value != 'staff' &&
                                      !row.removed &&
                                      conversation.canPost)
                                    TextButton(
                                      onPressed: _pending != null
                                          ? null
                                          : () => setState(() {
                                              _reply = row;
                                              _shared = false;
                                            }),
                                      child: Text(l('Reply privately')),
                                    ),
                                  if (row.canRemove)
                                    TextButton(
                                      onPressed: _pending != null || _sending
                                          ? null
                                          : () => _remove(row),
                                      child: Text(l('Remove message')),
                                    ),
                                ],
                              ),
                            ],
                          ),
                        ),
                      ),
                    if (conversation.canPost &&
                        _outcome == null &&
                        _removing == null)
                      _editor(),
                    if ((!conversation.thread.closed &&
                            conversation.canClose) ||
                        (conversation.thread.closed && conversation.canReopen))
                      TextButton(
                        onPressed:
                            _pending != null ||
                                _sending ||
                                _body.text.isNotEmpty ||
                                _removing != null
                            ? null
                            : () => _write('state', {
                                'revision': conversation.thread.revision,
                                'closed': !conversation.thread.closed,
                              }, thread: conversation.thread.id),
                        child: Text(
                          l(
                            conversation.thread.closed
                                ? 'Reopen conversation'
                                : 'Close conversation',
                          ),
                        ),
                      ),
                  ] else if (_compose)
                    _editor()
                  else if (_page != null) ...[
                    if (_page!.writable && _outcome == null)
                      FilledButton.icon(
                        onPressed: () => setState(() {
                          _compose = true;
                          final policy = _page!.policy;
                          _audience = policy.staffLearners
                              ? 'learners'
                              : policy.staffGuardians
                              ? 'guardians'
                              : 'staff';
                        }),
                        icon: const Icon(Icons.add_comment_outlined),
                        label: Text(
                          l(
                            widget.repository.persona == 'staff'
                                ? 'Start group announcement'
                                : 'Ask group staff privately',
                          ),
                        ),
                      ),
                    if (_page!.items.isEmpty)
                      Text(l('No conversations are available.')),
                    for (final row in _page!.items)
                      Card(
                        child: ListTile(
                          title: Text(row.title ?? l('Conversation')),
                          subtitle: Text(
                            '${l(row.private ? 'Private question' : 'Group conversation')} • ${row.unread} ${l('unread')}',
                          ),
                          trailing: const Icon(Icons.chevron_right),
                          onTap: _loading ? null : () => _load(thread: row.id),
                        ),
                      ),
                    if (_page!.nextBefore != null)
                      TextButton(
                        onPressed: _loading ? null : () => _load(older: true),
                        child: Text(l('Earlier conversations')),
                      ),
                  ],
                ],
              ),
      ),
    );
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    _epoch++;
    _erase();
    _body.dispose();
    _title.dispose();
    _reason.dispose();
    _scroll.dispose();
    _events?.cancel();
    _refreshes?.cancel();
    _denials?.cancel();
    super.dispose();
  }
}
