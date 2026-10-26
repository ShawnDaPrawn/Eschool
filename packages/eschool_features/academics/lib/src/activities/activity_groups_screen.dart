import 'dart:async';
import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:eschool_people/eschool_people.dart';
import 'package:uuid/uuid.dart';

import '../content/content_widgets.dart';
import 'activity_repository.dart';
import 'activity_file_repository.dart';
import 'activity_files_screen.dart';
import 'activity_write_screen.dart';
import 'group_chat_repository.dart';
import 'group_chat_screen.dart';

/// One private route keeps group lists, registers and drafts inside the app's
/// account/school boundary, including internal navigation on compact devices.
class ActivityGroupsScreen extends StatefulWidget {
  const ActivityGroupsScreen({
    required this.repository,
    this.pickFile = pickProfileFile,
    super.key,
  });
  final ProfileFilePicker pickFile;
  final ActivityRepository repository;
  @override
  State<ActivityGroupsScreen> createState() => _ActivityGroupsScreenState();
}

class _ActivityGroupsScreenState extends State<ActivityGroupsScreen>
    with WidgetsBindingObserver {
  ActivityFileRepository? _files;
  GroupChatRepository? _chat;
  MaterialFileIntent? _upload;
  bool _picking = false, _uploading = false;
  int _fileRefresh = 0;
  final Stopwatch _clock = Stopwatch()..start();
  void _eraseUpload() {
    _upload?.erase();
    _upload = null;
    _uploading = false;
  }

  ActivityGroupList? _list;
  ActivityGroupDetail? _detail;
  int _page = 1, _epoch = 0;
  bool _loading = true, _hidden = false, _dirty = false, _denied = false;
  String? _error, _mode;
  LiveSession? _live;
  Stream<LiveInvalidation>? _liveEvents;
  StreamSubscription<LiveInvalidation>? _liveSubscription;
  StreamSubscription<void>? _liveRefreshes;
  StreamSubscription<int>? _liveDenials;
  Timer? _liveTimer;
  int? _joinedGroup, _onlineCount;
  bool _liveChanged = false;
  Map<String, dynamic>? _record;
  String l(String s) => contentLabel(context, s);
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _load();
  }

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    final session = LiveUpdatesScope.sessionOf(context);
    final events = LiveUpdatesScope.eventsOf(context);
    if (identical(session, _live) && identical(events, _liveEvents)) return;
    if (_joinedGroup != null) unawaited(_live?.leaveGroup(_joinedGroup!));
    _liveSubscription?.cancel();
    _liveRefreshes?.cancel();
    _liveDenials?.cancel();
    _live = session;
    _liveEvents = events;
    _joinedGroup = null;
    _onlineCount = null;
    _liveSubscription = events.listen((event) {
      if (!mounted ||
          _hidden ||
          !widget.repository.isActive ||
          event.schoolId != widget.repository.schoolId ||
          event.groupId != _detail?.group.id) {
        return;
      }
      if (event.type == 'group.presence') {
        setState(() => _onlineCount = event.onlineCount);
      } else if (event.type == 'activity.group_changed') {
        _refreshFromLive();
      }
    });
    _liveRefreshes = LiveUpdatesScope.refreshesOf(
      context,
    ).listen((_) => _refreshFromLive());
    _liveDenials = session?.deniedGroups.listen((group) {
      if (!mounted || group != _detail?.group.id) return;
      _epoch++;
      setState(() {
        _denied = true;
        _chat = null;
        _files = null;
        _picking = false;
        _eraseUpload();
        _detail = null;
        _list = null;
        _mode = null;
        _record = null;
        _dirty = false;
        _onlineCount = null;
      });
    });
    _syncLiveGroup();
  }

  void _syncLiveGroup() {
    final group = _detail?.group.id;
    if (group == _joinedGroup) return;
    if (_joinedGroup != null) unawaited(_live?.leaveGroup(_joinedGroup!));
    _joinedGroup = group;
    _onlineCount = null;
    if (group != null) unawaited(_live?.joinGroup(group));
  }

  void _refreshFromLive() {
    if (!mounted || _hidden || _denied || !widget.repository.isActive) return;
    _liveTimer?.cancel();
    _liveTimer = Timer(const Duration(milliseconds: 300), () {
      if (!mounted || _hidden || _denied || !widget.repository.isActive) return;
      if (_loading) {
        _refreshFromLive();
        return;
      }
      if (_dirty || _mode != null || _picking || _upload != null) {
        setState(() => _liveChanged = true);
        return;
      }
      _liveChanged = false;
      unawaited(_load());
    });
  }

  Future<void> _acceptLiveRefresh() async {
    if (_picking) return;
    if (!await _leave() || !mounted) return;
    setState(() {
      _mode = null;
      _record = null;
      _dirty = false;
      _liveChanged = false;
    });
    await _load();
  }

  @override
  void didUpdateWidget(covariant ActivityGroupsScreen old) {
    super.didUpdateWidget(old);
    if (!identical(old.repository, widget.repository) ||
        old.repository.schoolId != widget.repository.schoolId ||
        old.repository.accountId != widget.repository.accountId ||
        !old.repository.isActive) {
      _files = null;
      _chat = null;
      _picking = false;
      _eraseUpload();
      _list = null;
      _detail = null;
      _mode = null;
      _dirty = false;
      _load();
    }
  }

  @override
  void dispose() {
    _epoch++;
    _eraseUpload();
    _liveTimer?.cancel();
    _liveSubscription?.cancel();
    _liveRefreshes?.cancel();
    _liveDenials?.cancel();
    if (_joinedGroup != null) unawaited(_live?.leaveGroup(_joinedGroup!));
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      setState(() => _hidden = false);
      if (!_picking) _load(group: _files?.groupId);
    } else if ([
      AppLifecycleState.inactive,
      AppLifecycleState.paused,
      AppLifecycleState.hidden,
    ].contains(state)) {
      if (_upload != null) _dirty = false;
      _eraseUpload();
      setState(() => _hidden = true);
    }
  }

  Future<void> _load({int? group}) async {
    final epoch = ++_epoch;
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final id = group ?? _detail?.group.id;
      final list = id == null
          ? await widget.repository.groups(page: _page)
          : null;
      final detail = id == null ? null : await widget.repository.detail(id);
      if (!mounted || epoch != _epoch) return;
      if (!widget.repository.isActive) {
        throw ApiException('activity_scope_changed', statusCode: 403);
      }
      setState(() {
        _denied = false;
        _loading = false;
        if (list != null) _list = list;
        _detail = detail;
        if (detail?.chatAvailable != true) _chat = null;
        if (_mode != null && detail?.canCapture != true) {
          _mode = null;
          _record = null;
          _dirty = false;
        }
      });
      _syncLiveGroup();
    } catch (e) {
      if (!mounted || epoch != _epoch) return;
      final denied =
          !widget.repository.isActive ||
          (e is ApiException && [401, 403, 404].contains(e.statusCode));
      setState(() {
        _loading = false;
        _error = 'Connect to refresh your activity access.';
        if (denied) {
          _denied = true;
          _chat = null;
          _files = null;
          _eraseUpload();
          _detail = null;
          _list = null;
          _mode = null;
          _record = null;
          _dirty = false;
        }
      });
    }
  }

  Future<bool> _leave() async {
    if (!_dirty) return true;
    return await showDialog<bool>(
          context: context,
          builder: (c) => AlertDialog(
            title: Text(l('Leave this draft?')),
            content: Text(
              l(
                'Unsaved changes will be lost. If a save result is unknown, retry the same operation before leaving.',
              ),
            ),
            actions: [
              TextButton(
                onPressed: () => Navigator.pop(c, false),
                child: Text(l('Keep editing')),
              ),
              TextButton(
                onPressed: () => Navigator.pop(c, true),
                child: Text(l('Leave draft')),
              ),
            ],
          ),
        ) ??
        false;
  }

  Future<void> _back() async {
    if (!await _leave() || !mounted) return;
    if (_files != null) {
      final group = _files!.groupId;
      setState(() {
        _files = null;
        _picking = false;
        _eraseUpload();
        _dirty = false;
      });
      await _load(group: group);
    } else if (_mode != null) {
      setState(() {
        _mode = null;
        _record = null;
        _dirty = false;
      });
      await _load();
    } else if (_detail != null) {
      setState(() => _detail = null);
      await _load();
    }
  }

  Future<void> _chooseDocument() async {
    final files = _files;
    if (files == null || _picking || _uploading || _upload != null) return;
    final owner = widget.repository,
        deadline = _clock.elapsed + const Duration(seconds: 60);
    bool current() =>
        mounted &&
        identical(owner, widget.repository) &&
        owner.isActive &&
        identical(files, _files);
    ProfileUpload? selected;
    _epoch++;
    setState(() {
      _list = null;
      _detail = null;
      _record = null;
      _mode = null;
      _picking = true;
      _error = null;
    });
    _syncLiveGroup();
    try {
      await WidgetsBinding.instance.endOfFrame;
      if (!current()) return;
      selected = await widget.pickFile();
      for (var count = 0; count < 125 && current() && _hidden; count++) {
        await Future<void>.delayed(const Duration(milliseconds: 16));
      }
      if (!current() || _hidden) return;
      final page = await files.listing();
      if (!current() || _hidden) return;
      if (_clock.elapsed > deadline) {
        throw ApiException(
          'The document selection expired. Choose it again.',
          isApiMessage: true,
        );
      }
      if (!page.canUpload) {
        throw ApiException(
          'Your group access changed. Reopen Activity groups.',
          statusCode: 403,
          isApiMessage: true,
        );
      }
      if (selected != null) {
        _upload = MaterialFileIntent(
          operationId: const Uuid().v4(),
          materialRevision: page.materialRevision,
          name: selected.name,
          bytes: selected.bytes,
        );
        selected = null; // ownership transfers to the foreground exact intent
        _dirty = true;
      }
    } catch (error) {
      if (current()) {
        setState(
          () => _error = error is ApiException && error.isApiMessage
              ? error.errorMessage
              : 'The documents could not be loaded. Try again.',
        );
      }
    } finally {
      selected?.bytes.fillRange(0, selected.bytes.length, 0);
      if (current()) setState(() => _picking = false);
    }
    if (!current() || _hidden) return;
    if (_upload != null) {
      await _sendUpload();
    } else {
      await _load(group: files.groupId);
    }
  }

  Future<void> _sendUpload() async {
    final files = _files, intent = _upload;
    if (files == null || intent == null || _uploading || intent.erased) return;
    bool current() =>
        mounted &&
        !_hidden &&
        widget.repository.isActive &&
        identical(files, _files) &&
        identical(intent, _upload);
    setState(() {
      _uploading = true;
      _error = null;
    });
    try {
      await files.upload(intent);
      if (!current()) {
        intent.erase();
        if (mounted && identical(intent, _upload)) {
          setState(() {
            _eraseUpload();
            _dirty = false;
            _files = null;
            _detail = null;
            _list = null;
            _denied = true;
          });
        }
        return;
      }
      setState(() {
        _eraseUpload();
        _dirty = false;
        _fileRefresh++;
      });
      await _load(group: files.groupId);
    } catch (error) {
      if (!current()) {
        intent.erase();
        if (mounted && identical(intent, _upload)) {
          setState(() {
            _eraseUpload();
            _dirty = false;
            _files = null;
            _detail = null;
            _list = null;
            _denied = true;
          });
        }
        return;
      }
      setState(() {
        _uploading = false;
        if (error is ApiException && !error.isTransportFailure) {
          _eraseUpload();
          _dirty = false;
        }
        _error = error is ApiException && error.isApiMessage
            ? error.errorMessage
            : 'The upload may have finished. Retry the same upload to confirm it.';
      });
    }
  }

  void _open(String mode, [Map<String, dynamic>? record]) {
    setState(() {
      _mode = mode;
      _record = record;
      _dirty = false;
    });
  }

  Widget _groups() {
    final list = _list;
    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        Text(
          l('Groups available to your school account'),
          style: Theme.of(context).textTheme.titleMedium,
        ),
        Text(
          l(
            'Staff allocations and your family memberships are shown together.',
          ),
        ),
        if (list?.items.isEmpty ?? true)
          Padding(
            padding: const EdgeInsets.all(24),
            child: Text(l('No current activity groups.')),
          ),
        for (final group in list?.items ?? <ActivityGroup>[])
          Card(
            child: ListTile(
              title: Text(group.name),
              subtitle: Text(
                '${group.discipline} · ${group.season}\n${group.startsOn} – ${group.endsOn}',
              ),
              isThreeLine: true,
              trailing: const Icon(Icons.chevron_right),
              onTap: _loading ? null : () => _load(group: group.id),
            ),
          ),
        if (list != null)
          Wrap(
            alignment: WrapAlignment.spaceBetween,
            crossAxisAlignment: WrapCrossAlignment.center,
            spacing: 12,
            runSpacing: 8,
            children: [
              TextButton(
                onPressed: !_loading && _page > 1
                    ? () {
                        _page--;
                        _load();
                      }
                    : null,
                child: Text(l('Previous page')),
              ),
              Text('${list.page}/${list.lastPage}'),
              TextButton(
                onPressed: !_loading && _page < list.lastPage
                    ? () {
                        _page++;
                        _load();
                      }
                    : null,
                child: Text(l('Next page')),
              ),
            ],
          ),
      ],
    );
  }

  Widget _group() {
    final detail = _detail!;
    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        Text(
          detail.group.name,
          style: Theme.of(context).textTheme.headlineSmall,
        ),
        Text(
          '${detail.group.discipline} · ${detail.group.season}\n${detail.group.startsOn} – ${detail.group.endsOn}',
        ),
        if (_onlineCount != null)
          Text(
            '${l('Online now')}: $_onlineCount',
            key: const ValueKey('activity-online-count'),
          ),
        if (detail.chatAvailable == true &&
            widget.repository is GroupChatProvider)
          FilledButton.icon(
            onPressed: _loading || _error != null
                ? null
                : () => setState(
                    () => _chat = (widget.repository as GroupChatProvider).chat(
                      detail.group.id,
                    ),
                  ),
            icon: const Icon(Icons.forum_outlined),
            label: Text(l('Group conversations')),
          ),
        if (detail.canCapture)
          Wrap(
            spacing: 8,
            children: [
              FilledButton.icon(
                onPressed: _loading || _error != null
                    ? null
                    : () => _open('session'),
                icon: const Icon(Icons.event_outlined),
                label: Text(l('New session')),
              ),
              OutlinedButton.icon(
                onPressed: _loading || _error != null
                    ? null
                    : () => _open('material'),
                icon: const Icon(Icons.article_outlined),
                label: Text(l('New material')),
              ),
            ],
          ),
        Padding(
          padding: const EdgeInsets.symmetric(vertical: 16),
          child: Text(
            l('Sessions and attendance'),
            style: Theme.of(context).textTheme.titleLarge,
          ),
        ),
        if (detail.sessions.isEmpty) Text(l('No sessions planned.')),
        for (final session in detail.sessions)
          Card(
            child: Padding(
              padding: const EdgeInsets.all(12),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    session.name,
                    style: Theme.of(context).textTheme.titleMedium,
                  ),
                  Text(
                    '${session.onDate} · ${session.venue} · ${l(session.status.value)}',
                  ),
                  if (detail.canCapture)
                    Wrap(
                      spacing: 8,
                      children: [
                        TextButton(
                          onPressed: _loading || _error != null
                              ? null
                              : () => _open('session', session.toJson()),
                          child: Text(l('Edit session')),
                        ),
                        if (session.status.value != 'cancelled')
                          TextButton(
                            onPressed: _loading || _error != null
                                ? null
                                : () => _open('attendance', session.toJson()),
                            child: Text(l('Open attendance')),
                          ),
                      ],
                    ),
                ],
              ),
            ),
          ),
        Padding(
          padding: const EdgeInsets.symmetric(vertical: 16),
          child: Text(
            l('Group materials'),
            style: Theme.of(context).textTheme.titleLarge,
          ),
        ),
        if (detail.materials.isEmpty) Text(l('No group materials yet.')),
        for (final material in detail.materials)
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    material.title,
                    style: Theme.of(context).textTheme.titleMedium,
                  ),
                  if (!material.published) Text(l('Private draft')),
                  ContentMarkdownView(markdown: material.markdown),
                  if (widget.repository is ActivityFileProvider)
                    TextButton.icon(
                      onPressed: _loading || _error != null
                          ? null
                          : () => setState(() {
                              _files =
                                  (widget.repository as ActivityFileProvider)
                                      .files(detail.group.id, material.id);
                              _fileRefresh++;
                            }),
                      icon: const Icon(Icons.attach_file),
                      label: Text(l('Documents')),
                    ),
                  if (detail.canCapture &&
                      (detail.canManage ||
                          material.authorId == widget.repository.accountId))
                    TextButton(
                      onPressed: _loading || _error != null
                          ? null
                          : () => _open('material', material.toJson()),
                      child: Text(l('Edit material')),
                    ),
                ],
              ),
            ),
          ),
      ],
    );
  }

  @override
  Widget build(BuildContext context) {
    final chat = _chat;
    if (chat != null) {
      return GroupChatScreen(
        key: ValueKey(chat),
        repository: chat,
        onlineCount: _onlineCount,
        onClose: () {
          setState(() => _chat = null);
          _load();
        },
      );
    }
    final privateBody = _picking
        ? Center(child: Text(l('Finish choosing the private document.')))
        : _upload != null
        ? ListView(
            padding: const EdgeInsets.all(16),
            children: [
              Text(
                l(
                  'The upload may have finished. Retry the same upload to confirm it.',
                ),
              ),
              if (_uploading) const LinearProgressIndicator(),
              FilledButton(
                onPressed: _uploading ? null : _sendUpload,
                child: Text(l('Retry the same upload')),
              ),
            ],
          )
        : _files != null
        ? ActivityFilesScreen(
            key: ValueKey((_files, _fileRefresh)),
            repository: _files!,
            onChoose: _chooseDocument,
            enabled: !_loading && _error == null && !_hidden,
          )
        : _mode != null && _detail != null
        ? ActivityWriteScreen(
            key: ValueKey('$_mode:${_record?['id'] ?? 0}'),
            repository: widget.repository,
            detail: _detail!,
            mode: _mode!,
            record: _record,
            enabled: !_loading && _error == null && !_hidden,
            onDirty: (value) {
              _dirty = value;
            },
            onSaved: () async {
              setState(() {
                _mode = null;
                _record = null;
                _dirty = false;
              });
              ScaffoldMessenger.of(
                context,
              ).showSnackBar(SnackBar(content: Text(l('Activity saved'))));
              await _load();
            },
          )
        : _detail != null
        ? _group()
        : _groups();
    return PopScope(
      canPop:
          _detail == null &&
          _mode == null &&
          _files == null &&
          !_dirty &&
          !_picking,
      onPopInvokedWithResult: (didPop, _) {
        if (!didPop) _back();
      },
      child: Scaffold(
        appBar: AppBar(
          title: Text(l('Activity groups')),
          leading: _detail == null && _files == null
              ? null
              : IconButton(
                  tooltip: MaterialLocalizations.of(context).backButtonTooltip,
                  onPressed: _back,
                  icon: const Icon(Icons.arrow_back),
                ),
          actions: [
            IconButton(
              tooltip: l('Refresh activity access'),
              onPressed: _loading || _picking || _upload != null
                  ? null
                  : () => _load(group: _files?.groupId),
              icon: const Icon(Icons.refresh),
            ),
          ],
        ),
        body: Stack(
          children: [
            Offstage(
              offstage: _hidden || _denied || _loading,
              child: Column(
                children: [
                  if (_liveChanged)
                    MaterialBanner(
                      content: Text(l('Updates available')),
                      actions: [
                        TextButton(
                          onPressed: _acceptLiveRefresh,
                          child: Text(l('Refresh activity access')),
                        ),
                      ],
                    ),
                  if (_error != null)
                    MaterialBanner(
                      content: Text(l(_error!)),
                      actions: [
                        TextButton(onPressed: _load, child: Text(l('Retry'))),
                      ],
                    ),
                  Expanded(
                    key: const ValueKey('activity-private-body'),
                    child: privateBody,
                  ),
                ],
              ),
            ),
            if (_loading && !_hidden)
              const Center(child: CircularProgressIndicator()),
            if (_denied && !_hidden)
              Center(child: Text(l('Not available for this account'))),
          ],
        ),
      ),
    );
  }
}
