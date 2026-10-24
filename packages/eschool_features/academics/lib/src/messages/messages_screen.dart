import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../content/content_widgets.dart';
import 'message_repository.dart';
import 'message_cubit.dart';
import 'message_reader_screen.dart';
import 'message_editor_screen.dart';

class _MessageChoice {
  const _MessageChoice({this.id, this.prepared = false, this.discover = false});
  final int? id;
  final bool prepared, discover;
  String get key => '${id ?? 'new'}:$prepared:$discover';
}

class SchoolMessagesScreen extends StatefulWidget {
  const SchoolMessagesScreen({
    required this.repository,
    this.initialMessage,
    super.key,
  });
  final SchoolMessageRepository repository;
  final int? initialMessage;
  @override
  State<SchoolMessagesScreen> createState() => _SchoolMessagesScreenState();
}

class _SchoolMessagesScreenState extends State<SchoolMessagesScreen>
    with WidgetsBindingObserver {
  late SchoolMessageRepository _repository;
  late MessageListCubit _cubit;
  CommunicationConfiguration? _lastConfiguration;
  _MessageChoice? _choice;
  bool _privateEdits = false, _suspended = false;
  bool _initialPending = false;
  int _mode = 0, _refreshEpoch = 0;
  final Set<int> _readMessages = {};
  String l(String text) => contentLabel(context, text);
  void _create() {
    _repository = widget.repository;
    _lastConfiguration = null;
    _choice = widget.initialMessage == null
        ? null
        : _MessageChoice(id: widget.initialMessage, discover: true);
    _initialPending = widget.initialMessage != null;
    _privateEdits = false;
    _mode = 0;
    _readMessages.clear();
    _cubit = MessageListCubit(_repository)..load();
  }

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _create();
  }

  @override
  void didUpdateWidget(covariant SchoolMessagesScreen old) {
    super.didUpdateWidget(old);
    if (_repository.schoolId != widget.repository.schoolId ||
        _repository.readerId != widget.repository.readerId ||
        !_repository.isActive) {
      _cubit.close();
      _create();
    }
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      if (mounted) setState(() => _suspended = false);
      _cubit.load();
    } else if ([
      AppLifecycleState.inactive,
      AppLifecycleState.paused,
      AppLifecycleState.hidden,
    ].contains(state)) {
      if (mounted) setState(() => _suspended = true);
    }
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    _cubit.close();
    super.dispose();
  }

  Future<bool> _canChange() async {
    if (!_privateEdits) return true;
    return await showDialog<bool>(
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
        ) ??
        false;
  }

  Future<void> _open(
    _MessageChoice choice, {
    required bool wide,
    required bool staff,
  }) async {
    if (!await _canChange() || !mounted) return;
    _privateEdits = false;
    if (wide) {
      setState(() => _choice = choice);
      return;
    }
    await Navigator.of(context).push(
      AdaptivePageRoute<void>(
        contentColumn: false,
        builder: (_) => _MessageDetail(
          repository: _repository,
          choice: choice,
          staff: staff,
          embedded: false,
        ),
      ),
    );
    if (mounted && _repository.isActive) _cubit.load();
  }

  Future<void> _switch(int mode, bool staff) async {
    if (mode == _mode || !await _canChange() || !mounted) return;
    setState(() {
      _mode = mode;
      _choice = null;
      _privateEdits = false;
    });
    await _cubit.load(prepared: staff && mode == 1);
  }

  Future<void> _refresh() {
    setState(() => _refreshEpoch++);
    return _cubit.load();
  }

  void _read(int id) {
    if (mounted && _readMessages.add(id)) setState(() {});
  }

  @override
  Widget build(BuildContext context) => ScreenScaffold(
    title: l('School messages'),
    action: IconButton(
      tooltip: l('Refresh messages'),
      onPressed: _refresh,
      icon: const Icon(Icons.refresh),
    ),
    body: BlocBuilder<MessageListCubit, MessageListState>(
      bloc: _cubit,
      builder: (context, state) {
        if (state.configuration != null) {
          _lastConfiguration = state.configuration;
        }
        final config = state.configuration ?? _lastConfiguration;
        final visible =
            state.configuration != null && _repository.isActive && !_suspended;
        final staff = config?.staffAvailable ?? false;
        return Stack(
          children: [
            if (config != null)
              Offstage(
                offstage: !visible,
                child: AdaptiveScaffold(
                  maxContentWidth: 1600,
                  selectedIndex: _mode,
                  onDestinationSelected: (index) => _switch(index, staff),
                  destinations: [
                    AdaptiveDestination(
                      icon: const Icon(Icons.inbox_outlined),
                      selectedIcon: const Icon(Icons.inbox),
                      label: l('Inbox'),
                    ),
                    AdaptiveDestination(
                      icon: Icon(
                        staff
                            ? Icons.edit_note_outlined
                            : Icons.mark_email_unread_outlined,
                      ),
                      selectedIcon: Icon(
                        staff ? Icons.edit_note : Icons.mark_email_unread,
                      ),
                      label: l(staff ? 'Drafts and reviews' : 'Unread'),
                    ),
                  ],
                  body: LayoutBuilder(
                    builder: (context, constraints) {
                      final wide = constraints.maxWidth >= 800;
                      if (visible && _initialPending && _choice != null) {
                        _initialPending = false;
                        if (!wide) {
                          final choice = _choice!;
                          WidgetsBinding.instance.addPostFrameCallback((_) {
                            if (mounted &&
                                _repository.isActive &&
                                !_suspended &&
                                _cubit.state.configuration != null) {
                              _open(choice, wide: false, staff: staff);
                            }
                          });
                        }
                      }
                      final rows = state.items
                          .where(
                            (row) =>
                                staff ||
                                _mode == 0 ||
                                row.readAt == null &&
                                    !_readMessages.contains(row.id),
                          )
                          .toList();
                      final list = Column(
                        children: [
                          if (Breakpoints.isPhone(context))
                            Padding(
                              padding: const EdgeInsets.all(12),
                              child: SegmentedButton<int>(
                                segments: [
                                  ButtonSegment(
                                    value: 0,
                                    label: Text(l('Inbox')),
                                  ),
                                  ButtonSegment(
                                    value: 1,
                                    label: Text(
                                      l(
                                        staff ? 'Drafts and reviews' : 'Unread',
                                      ),
                                    ),
                                  ),
                                ],
                                selected: {_mode},
                                onSelectionChanged: (selection) =>
                                    _switch(selection.single, staff),
                              ),
                            ),
                          if (staff)
                            Padding(
                              padding: const EdgeInsets.all(12),
                              child: FilledButton.icon(
                                onPressed:
                                    state.busy ||
                                        config.scopes.isEmpty &&
                                            !config.audiences.any(
                                              (a) => a.active,
                                            )
                                    ? null
                                    : () => _open(
                                        const _MessageChoice(prepared: true),
                                        wide: wide,
                                        staff: staff,
                                      ),
                                icon: const Icon(Icons.add),
                                label: Text(l('New school message')),
                              ),
                            ),
                          Expanded(
                            child: RefreshIndicator(
                              onRefresh: _refresh,
                              child: ListView.builder(
                                physics: const AlwaysScrollableScrollPhysics(),
                                padding: const EdgeInsets.all(12),
                                itemCount: rows.length + 1,
                                itemBuilder: (context, index) {
                                  if (index == rows.length) {
                                    if (state.page < state.lastPage) {
                                      return TextButton(
                                        onPressed: state.busy
                                            ? null
                                            : () => _cubit.load(more: true),
                                        child: Text(l('Load more messages')),
                                      );
                                    }
                                    return Padding(
                                      padding: const EdgeInsets.all(20),
                                      child: Text(
                                        l(
                                          rows.isEmpty
                                              ? 'No messages in this view.'
                                              : 'All current messages are shown.',
                                        ),
                                      ),
                                    );
                                  }
                                  final row = rows[index],
                                      prepared = staff && _mode == 1;
                                  return Card(
                                    child: ListTile(
                                      selected: _choice?.id == row.id,
                                      leading: Icon(
                                        prepared
                                            ? Icons.edit_note_outlined
                                            : row.readAt == null &&
                                                  !_readMessages.contains(
                                                    row.id,
                                                  )
                                            ? Icons.mark_email_unread_outlined
                                            : Icons.drafts_outlined,
                                      ),
                                      title: Text(row.title),
                                      subtitle: Text(
                                        prepared
                                            ? l(row.state.value)
                                            : l(
                                                row.readAt == null &&
                                                        !_readMessages.contains(
                                                          row.id,
                                                        )
                                                    ? 'Unread'
                                                    : 'Read',
                                              ),
                                      ),
                                      onTap: () => _open(
                                        _MessageChoice(
                                          id: row.id,
                                          prepared: prepared,
                                        ),
                                        wide: wide,
                                        staff: staff,
                                      ),
                                    ),
                                  );
                                },
                              ),
                            ),
                          ),
                        ],
                      );
                      if (!wide) return list;
                      return Row(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          SizedBox(width: 300, child: list),
                          const VerticalDivider(width: 1),
                          Expanded(
                            child: _choice == null
                                ? Center(
                                    child: Text(
                                      l('Choose a message to read or review.'),
                                    ),
                                  )
                                : _MessageDetail(
                                    key: ValueKey(_choice!.key),
                                    repository: _repository,
                                    choice: _choice!,
                                    staff: staff,
                                    embedded: true,
                                    onPrivateEdits: (value) =>
                                        _privateEdits = value,
                                    refreshEpoch: _refreshEpoch,
                                    onRead: _read,
                                  ),
                          ),
                        ],
                      );
                    },
                  ),
                ),
              ),
            if (!visible)
              Center(
                child: state.busy
                    ? const CircularProgressIndicator()
                    : ContentMessage(
                        state.error ?? 'Connect to open school messages.',
                        retry: () => _cubit.load(),
                      ),
              ),
          ],
        );
      },
    ),
  );
}

class _MessageDetail extends StatefulWidget {
  const _MessageDetail({
    required this.repository,
    required this.choice,
    required this.staff,
    required this.embedded,
    this.onPrivateEdits,
    this.refreshEpoch = 0,
    this.onRead,
    super.key,
  });
  final SchoolMessageRepository repository;
  final _MessageChoice choice;
  final bool staff, embedded;
  final ValueChanged<bool>? onPrivateEdits;
  final int refreshEpoch;
  final ValueChanged<int>? onRead;
  @override
  State<_MessageDetail> createState() => _MessageDetailState();
}

class _MessageDetailState extends State<_MessageDetail> {
  bool? _editor;
  String? _error;
  @override
  void initState() {
    super.initState();
    _discover();
  }

  Future<void> _discover() async {
    if (!widget.choice.discover || !widget.staff) {
      _editor = widget.choice.prepared;
      return;
    }
    try {
      await widget.repository.editor(widget.choice.id!);
      if (mounted) setState(() => _editor = true);
    } catch (error) {
      if (!mounted) return;
      if (error is ApiException && error.statusCode == 404) {
        setState(() => _editor = false);
      } else {
        setState(() => _error = messageFailure(error));
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_error != null) return ContentMessage(_error!);
    if (_editor == null) {
      return const Center(child: CircularProgressIndicator());
    }
    if (_editor! && !widget.staff) {
      return const ContentMessage('Not available for this account');
    }
    if (_editor!) {
      return SchoolMessageEditorScreen(
        repository: widget.repository,
        id: widget.choice.id,
        embedded: widget.embedded,
        onPrivateEdits: widget.onPrivateEdits,
        refreshEpoch: widget.refreshEpoch,
      );
    }
    return SchoolMessageReaderScreen(
      repository: widget.repository,
      id: widget.choice.id!,
      embedded: widget.embedded,
      refreshEpoch: widget.refreshEpoch,
      onRead: widget.onRead,
    );
  }
}
