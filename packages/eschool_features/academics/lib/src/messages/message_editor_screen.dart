import 'dart:convert';
import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../content/content_widgets.dart';
import 'message_repository.dart';
import 'message_cubit.dart';

class SchoolMessageEditorScreen extends StatefulWidget {
  const SchoolMessageEditorScreen({
    required this.repository,
    this.id,
    this.embedded = false,
    this.onPrivateEdits,
    this.refreshEpoch = 0,
    super.key,
  });
  final SchoolMessageRepository repository;
  final int? id;
  final bool embedded;
  final ValueChanged<bool>? onPrivateEdits;
  final int refreshEpoch;
  @override
  State<SchoolMessageEditorScreen> createState() =>
      _SchoolMessageEditorScreenState();
}

class _SchoolMessageEditorScreenState extends State<SchoolMessageEditorScreen>
    with WidgetsBindingObserver {
  late MessageEditorCubit _cubit;
  final _title = TextEditingController(),
      _body = TextEditingController(),
      _schedule = TextEditingController();
  final _form = GlobalKey<FormState>();
  String? _scope;
  bool _parents = false, _allowPop = false;
  Set<String> _channels = {'in_app'};
  int _formVersion = -1;
  String l(String text) => contentLabel(context, text);
  void _create() {
    _formVersion = -1;
    _cubit = MessageEditorCubit(widget.repository, messageId: widget.id)
      ..load();
  }

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _create();
  }

  @override
  void didUpdateWidget(covariant SchoolMessageEditorScreen old) {
    super.didUpdateWidget(old);
    if (old.repository != widget.repository || old.id != widget.id) {
      _cubit.close();
      _create();
    } else if (old.refreshEpoch != widget.refreshEpoch)
      _reload();
  }

  void _reload() {
    _cubit.load(
      keepLocal:
          _cubit.state.dirty || _cubit.state.conflict || _cubit.state.uncertain,
    );
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      _reload();
    } else if ([
      AppLifecycleState.inactive,
      AppLifecycleState.paused,
      AppLifecycleState.hidden,
    ].contains(state))
      _cubit.hide();
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    _cubit.close();
    _title.dispose();
    _body.dispose();
    _schedule.dispose();
    super.dispose();
  }

  void _hydrate(MessageEditorState state) {
    if (!state.validated || state.formVersion == _formVersion) return;
    _formVersion = state.formVersion;
    final editor = state.editor, config = state.configuration!;
    _title.text = editor?.title ?? '';
    _body.text = editor?.markdown ?? '';
    _schedule.text = editor?.scheduledLocal ?? '';
    _parents = editor?.audience.parents ?? false;
    _channels = Set<String>.from(editor?.channels ?? {'in_app'});
    _scope = editor == null
        ? (config.scopes.isEmpty
              ? null
              : '${config.scopes.first.kind.value}:${config.scopes.first.targetId ?? 0}')
        : '${editor.audience.kind.value}:${editor.audience.targetId ?? 0}';
  }

  Future<void> _leave() async {
    final state = _cubit.state;
    if (!state.dirty && !state.uncertain && !_cubit.hasPendingWrite) {
      Navigator.of(context).pop();
      return;
    }
    final leave = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(l('Leave this draft?')),
        content: Text(
          l(
            'Unsaved edits will be lost. A change with an unknown result may already have saved; check your drafts before creating it again.',
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: Text(l('Keep editing')),
          ),
          TextButton(
            onPressed: () => Navigator.pop(context, true),
            child: Text(l('Leave draft')),
          ),
        ],
      ),
    );
    if (leave == true && mounted) {
      setState(() => _allowPop = true);
      Navigator.of(context).pop();
    }
  }

  Future<String?> _reason(String title) async {
    return showDialog<String>(
      context: context,
      builder: (_) => _MessageReasonDialog(title: l(title)),
    );
  }

  Future<void> _pickSchedule() async {
    final existing = DateTime.tryParse(_schedule.text), now = DateTime.now();
    final first = DateTime(2020), last = DateTime(now.year + 5, 12, 31);
    final initial =
        existing == null || existing.isBefore(first) || existing.isAfter(last)
        ? now
        : existing;
    final date = await showDatePicker(
      context: context,
      initialDate: initial,
      firstDate: first,
      lastDate: last,
      helpText: l('Choose a date in the school timezone'),
    );
    if (date == null || !mounted) return;
    final time = await showTimePicker(
      context: context,
      initialTime: TimeOfDay.fromDateTime(existing ?? now),
      helpText: l('Choose a time in the school timezone'),
    );
    if (time == null || !mounted) return;
    String two(int value) => value.toString().padLeft(2, '0');
    _schedule.text =
        '${date.year}-${two(date.month)}-${two(date.day)}T${two(time.hour)}:${two(time.minute)}';
    _cubit.changed();
  }

  Future<void> _save(MessageEditorState state) async {
    if (!_form.currentState!.validate() || _scope == null) return;
    final parts = _scope!.split(':');
    final audience = CommunicationAudienceSelection.fromJson({
      'kind': parts.first,
      'target_id': parts.first == 'school' ? null : int.parse(parts.last),
      'session_year_id': state.configuration!.sessionYearId,
      'parents': parts.first == 'custom' ? false : _parents,
    });
    await _cubit.save(
      title: _title.text.trim(),
      markdown: _body.text,
      audience: audience,
      channels: _channels,
      scheduledLocal: _schedule.text.trim().replaceFirst(' ', 'T'),
    );
  }

  @override
  Widget build(
    BuildContext context,
  ) => BlocConsumer<MessageEditorCubit, MessageEditorState>(
    bloc: _cubit,
    listener: (context, state) {
      _hydrate(state);
      widget.onPrivateEdits?.call(
        state.dirty || state.uncertain || _cubit.hasPendingWrite,
      );
    },
    builder: (context, state) {
      _hydrate(state);
      if (!state.validated) {
        final body = Center(
          child: state.busy
              ? const CircularProgressIndicator()
              : ContentMessage(
                  state.error ?? 'Connect to validate this private draft.',
                  retry: _reload,
                ),
        );
        return widget.embedded
            ? body
            : ScreenScaffold(title: l('School message draft'), body: body);
      }
      final config = state.configuration!, editor = state.editor;
      final author = editor?.isAuthor ?? true;
      final editable =
          author &&
          editor?.publishedAt == null &&
          editor?.state.value != 'withdrawn';
      final enabled = !state.busy && !state.uncertain && !state.conflict;
      final scopes = <String, String>{
        for (final row in config.scopes)
          '${row.kind.value}:${row.targetId ?? 0}':
              '${l(row.kind.value)} · ${row.name}',
        for (final row in config.audiences.where((a) => a.active))
          'custom:${row.id}': '${l('Custom list')} · ${row.name}',
      };
      final selected = scopes.containsKey(_scope) ? _scope : null;
      final content = SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Semantics(
              header: true,
              child: Text(
                editor?.title ?? l('New school message'),
                style: Theme.of(context).textTheme.headlineSmall,
              ),
            ),
            if (editor != null)
              Text(
                '${l(editor.state.value)} · ${l('Revision')} ${editor.revision}',
              ),
            Text('${l('School timezone')}: ${config.timezone}'),
            if (state.error != null)
              Padding(
                padding: const EdgeInsets.symmetric(vertical: 12),
                child: Text(
                  l(state.error!),
                  style: TextStyle(color: Theme.of(context).colorScheme.error),
                ),
              ),
            if (state.busy) const LinearProgressIndicator(),
            if (state.uncertain) ...[
              Text(
                l(
                  'The result is unknown. Retry the same change before editing or sending another one.',
                ),
              ),
              FilledButton.icon(
                onPressed: state.busy ? null : _cubit.retryPending,
                icon: const Icon(Icons.refresh),
                label: Text(l('Retry same message change')),
              ),
            ],
            if (state.conflict) ...[
              Text(
                l(
                  'The saved message may have changed. Compare it with your local text before continuing.',
                ),
              ),
              if (editor != null)
                ExpansionTile(
                  title: Text(l('Current saved text')),
                  children: [
                    Padding(
                      padding: const EdgeInsets.all(12),
                      child: ContentMarkdownView(markdown: editor.markdown),
                    ),
                  ],
                ),
              TextButton(
                onPressed: state.busy
                    ? null
                    : author
                    ? _cubit.acceptComparison
                    : () => _cubit.load(),
                child: Text(
                  l(
                    author
                        ? 'Keep my local text against the current revision'
                        : 'Use current saved message',
                  ),
                ),
              ),
            ],
            if (editable)
              Form(
                key: _form,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const SizedBox(height: 16),
                    TextFormField(
                      controller: _title,
                      enabled: enabled,
                      maxLength: 191,
                      decoration: InputDecoration(
                        labelText: l('Message subject'),
                      ),
                      validator: (value) => (value ?? '').trim().isEmpty
                          ? l('Enter a message subject.')
                          : null,
                      onChanged: (_) => _cubit.changed(),
                    ),
                    const SizedBox(height: 12),
                    DropdownButtonFormField<String>(
                      key: ValueKey(
                        '$_formVersion:$_scope:${scopes.keys.join(',')}',
                      ),
                      initialValue: selected,
                      isExpanded: true,
                      decoration: InputDecoration(
                        labelText: l('Audience scope'),
                      ),
                      items: [
                        for (final entry in scopes.entries)
                          DropdownMenuItem(
                            value: entry.key,
                            child: Text(
                              entry.value,
                              maxLines: 2,
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                      ],
                      onChanged: enabled
                          ? (value) {
                              setState(() => _scope = value);
                              _cubit.changed();
                            }
                          : null,
                      validator: (value) => value == null
                          ? l('Choose a current audience scope.')
                          : null,
                    ),
                    SwitchListTile(
                      contentPadding: EdgeInsets.zero,
                      title: Text(l('Parents and guardians of these learners')),
                      subtitle: _scope?.startsWith('custom:') == true
                          ? Text(
                              l(
                                'Custom lists use their explicit saved contacts.',
                              ),
                            )
                          : null,
                      value: _parents,
                      onChanged:
                          enabled && _scope?.startsWith('custom:') != true
                          ? (value) {
                              setState(() => _parents = value);
                              _cubit.changed();
                            }
                          : null,
                    ),
                    Text(
                      l('Delivery channels'),
                      style: Theme.of(context).textTheme.titleSmall,
                    ),
                    Wrap(
                      spacing: 8,
                      runSpacing: 8,
                      children: [
                        for (final entry in {
                          'in_app': 'In app',
                          'push': 'Push notification',
                          'email': 'Email',
                        }.entries)
                          FilterChip(
                            label: Text(l(entry.value)),
                            selected: _channels.contains(entry.key),
                            onSelected: enabled
                                ? (value) {
                                    setState(() {
                                      if (value) {
                                        _channels.add(entry.key);
                                      } else {
                                        _channels.remove(entry.key);
                                      }
                                    });
                                    _cubit.changed();
                                  }
                                : null,
                          ),
                      ],
                    ),
                    Text(
                      l(
                        'Mandatory school channels can be added automatically. Optional channels respect each persons choices.',
                      ),
                    ),
                    const SizedBox(height: 16),
                    TextFormField(
                      controller: _schedule,
                      enabled: enabled,
                      decoration: InputDecoration(
                        labelText: l(
                          'Schedule in school time; leave blank to send now',
                        ),
                        hintText: 'YYYY-MM-DDTHH:mm',
                        suffixIcon: IconButton(
                          tooltip: l('Choose school date and time'),
                          onPressed: enabled ? _pickSchedule : null,
                          icon: const Icon(Icons.event_outlined),
                        ),
                      ),
                      validator: (value) =>
                          (value ?? '').isEmpty ||
                              RegExp(
                                r'^\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}$',
                              ).hasMatch(value!)
                          ? null
                          : l('Use YYYY-MM-DDTHH:mm in school time.'),
                      onChanged: (_) => _cubit.changed(),
                    ),
                    const SizedBox(height: 16),
                    TextFormField(
                      controller: _body,
                      enabled: enabled,
                      minLines: 8,
                      maxLines: 20,
                      maxLength: 20000,
                      decoration: InputDecoration(
                        labelText: l('Message Markdown'),
                        alignLabelWithHint: true,
                      ),
                      validator: (value) => (value ?? '').trim().isEmpty
                          ? l('Enter a message body.')
                          : utf8.encode(value!).length > 65536
                          ? l(
                              'Keep the message within 20,000 characters and 64 KiB.',
                            )
                          : null,
                      onChanged: (_) => _cubit.changed(),
                    ),
                    if (_channels.isEmpty)
                      Text(
                        l('Choose at least one delivery channel.'),
                        style: TextStyle(
                          color: Theme.of(context).colorScheme.error,
                        ),
                      ),
                    FilledButton.icon(
                      onPressed: enabled && _channels.isNotEmpty
                          ? () => _save(state)
                          : null,
                      icon: const Icon(Icons.save_outlined),
                      label: Text(l('Save private message')),
                    ),
                  ],
                ),
              ),
            if (state.dirty)
              Text(
                l(
                  'Save your changes before previewing, withdrawing or submitting this message.',
                ),
              ),
            TextButton.icon(
              onPressed: state.busy ? null : _reload,
              icon: const Icon(Icons.refresh),
              label: Text(l('Refresh and compare message')),
            ),
            if (editor?.blockedCode != null)
              Text(
                l(
                  'Delivery needs review because the audience or authority changed, or no current recipients remain.',
                ),
              ),
            if (editor?.decisionReason != null)
              Text('${l('Review reason')}: ${editor!.decisionReason}'),
            if (editor?.publishedAt != null)
              Text(
                '${l('Recipients at publication')}: ${editor!.recipientCount} · ${l('Read acknowledgements')}: ${editor.readCount}',
              ),
            if (editor != null) ...[
              const Divider(height: 32),
              Semantics(
                header: true,
                child: Text(
                  l('Saved message preview'),
                  style: Theme.of(context).textTheme.titleMedium,
                ),
              ),
              ContentMarkdownView(markdown: editor.markdown),
              if (editor.publishedAt == null &&
                  editor.state.value != 'withdrawn')
                TextButton.icon(
                  onPressed: enabled && !state.dirty ? _cubit.preview : null,
                  icon: const Icon(Icons.preview_outlined),
                  label: Text(l('Preview saved message')),
                ),
            ],
            if (state.preview != null) ...[
              const Divider(height: 32),
              Semantics(
                header: true,
                child: Text(
                  l('Audience and delivery preview'),
                  style: Theme.of(context).textTheme.titleMedium,
                ),
              ),
              Text(
                '${l('Current recipients')}: ${state.preview!.recipientCount}',
              ),
              Text(
                l(
                  state.preview!.requiresApproval
                      ? 'Approval required'
                      : 'No approval required',
                ),
              ),
              Text(
                '${l('Channels')}: ${state.preview!.channels.map((c) => l(c)).join(', ')}',
              ),
              Text(
                '${l('Schedule')}: ${editor?.scheduledLocal ?? l('Send now')} (${config.timezone})',
              ),
              Text(
                l(
                  'Scheduled standard audiences resolve again at delivery time. Current sender and reviewer authority is checked before publication.',
                ),
              ),
              if (author &&
                  [
                    'draft',
                    'rejected',
                    'blocked',
                  ].contains(editor?.state.value))
                FilledButton(
                  onPressed:
                      enabled &&
                          !state.dirty &&
                          state.preview!.recipientCount > 0
                      ? _cubit.submitPreview
                      : null,
                  child: Text(l('Submit previewed message')),
                ),
              if (editor?.canReview == true && editor?.state.value == 'pending')
                Wrap(
                  spacing: 12,
                  children: [
                    FilledButton(
                      onPressed: enabled
                          ? () async {
                              final reason = await _reason('Approve message');
                              if (reason != null && mounted) {
                                await _cubit.decide(true, reason);
                              }
                            }
                          : null,
                      child: Text(l('Approve message')),
                    ),
                    OutlinedButton(
                      onPressed: enabled
                          ? () async {
                              final reason = await _reason(
                                'Return for changes',
                              );
                              if (reason != null && mounted) {
                                await _cubit.decide(false, reason);
                              }
                            }
                          : null,
                      child: Text(l('Return for changes')),
                    ),
                  ],
                ),
            ],
            if (editor != null && editor.state.value != 'withdrawn')
              Padding(
                padding: const EdgeInsets.only(top: 20),
                child: OutlinedButton(
                  onPressed: enabled && !state.dirty
                      ? () async {
                          final reason = await _reason('Withdraw message');
                          if (reason != null && mounted) {
                            await _cubit.withdraw(reason);
                          }
                        }
                      : null,
                  child: Text(l('Withdraw message')),
                ),
              ),
          ],
        ),
      );
      final body = PopScope(
        canPop:
            _allowPop ||
            !state.dirty && !state.uncertain && !_cubit.hasPendingWrite,
        onPopInvokedWithResult: (didPop, result) {
          if (!didPop) _leave();
        },
        child: content,
      );
      return widget.embedded
          ? body
          : ScreenScaffold(
              title: l('School message draft'),
              body: ContentColumn(
                maxWidth: 1100,
                child: Material(
                  color: Theme.of(context).colorScheme.surface,
                  child: body,
                ),
              ),
            );
    },
  );
}

class _MessageReasonDialog extends StatefulWidget {
  const _MessageReasonDialog({required this.title});
  final String title;
  @override
  State<_MessageReasonDialog> createState() => _MessageReasonDialogState();
}

class _MessageReasonDialogState extends State<_MessageReasonDialog> {
  final _controller = TextEditingController();
  final _form = GlobalKey<FormState>();
  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    String l(String text) => contentLabel(context, text);
    return AlertDialog(
      title: Text(widget.title),
      content: Form(
        key: _form,
        child: TextFormField(
          controller: _controller,
          autofocus: true,
          maxLength: 2000,
          minLines: 2,
          maxLines: 5,
          decoration: InputDecoration(labelText: l('Review reason')),
          validator: (value) => (value ?? '').trim().length < 3
              ? l('Enter a reason of at least three characters.')
              : null,
        ),
      ),
      actions: [
        TextButton(
          onPressed: () => Navigator.pop(context),
          child: Text(l('Cancel')),
        ),
        FilledButton(
          onPressed: () {
            if (_form.currentState!.validate()) {
              Navigator.pop(context, _controller.text.trim());
            }
          },
          child: Text(l('Continue')),
        ),
      ],
    );
  }
}
