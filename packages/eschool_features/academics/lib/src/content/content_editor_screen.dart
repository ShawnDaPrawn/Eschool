import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'content_repository.dart';
import 'content_cubit.dart';
import 'content_widgets.dart';

class ContentEditorScreen extends StatefulWidget {
  const ContentEditorScreen({
    required this.repository,
    required this.configuration,
    this.itemId,
    this.pickFile = pickLearningContent,
    super.key,
  });
  final AcademicContentRepository repository;
  final ContentConfiguration configuration;
  final int? itemId;
  final ContentFilePicker pickFile;
  @override
  State<ContentEditorScreen> createState() => _ContentEditorScreenState();
}

class _ContentEditorScreenState extends State<ContentEditorScreen>
    with WidgetsBindingObserver {
  late ContentEditorCubit _cubit;
  late ContentConfiguration _configuration;
  final _title = TextEditingController(),
      _summary = TextEditingController(),
      _markdown = TextEditingController();
  ContentGroup? _context;
  Set<int> _groups = {}, _topics = {};
  bool _dirty = false, _reviewed = false, _hydrating = false;
  bool _allowLeave = false, _foreground = true, _historyOpen = false;
  int _saved = 0;
  String? _loadedKey, _localError;
  ContentUpload? _file;
  String label(String text) => contentLabel(context, text);
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _configuration = widget.configuration;
    _context = _configuration.groups.firstOrNull;
    if (_context != null) _groups = {_context!.id};
    _cubit = ContentEditorCubit(widget.repository, itemId: widget.itemId)
      ..load();
    for (final controller in [_title, _summary, _markdown]) {
      controller.addListener(_changed);
    }
  }

  void _changed() {
    if (!_hydrating && mounted) {
      setState(() {
        _dirty = true;
        _reviewed = false;
      });
    }
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      _foreground = true;
      _cubit.load(keepConflict: _dirty && !_cubit.state.uncertain);
    } else if (state == AppLifecycleState.inactive ||
        state == AppLifecycleState.hidden ||
        state == AppLifecycleState.paused) {
      _foreground = false;
      if (_historyOpen && mounted) {
        Navigator.of(context).pop();
        _historyOpen = false;
      }
      _cubit.hide();
    }
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    _cubit.close();
    for (final c in [_title, _summary, _markdown]) {
      c.dispose();
    }
    super.dispose();
  }

  Future<bool> _leave() async {
    if (!_dirty && !_cubit.state.uncertain) return true;
    return await showDialog<bool>(
          context: context,
          builder: (context) => AlertDialog(
            title: Text(label('Keep your draft')),
            content: Text(
              label(
                'Leaving closes the private draft on this device. Saved material stays in the library. Check the library before repeating an uncertain upload.',
              ),
            ),
            actions: [
              TextButton(
                onPressed: () => Navigator.pop(context, false),
                child: Text(label('Keep editing')),
              ),
              TextButton(
                onPressed: () => Navigator.pop(context, true),
                child: Text(label('Leave screen')),
              ),
            ],
          ),
        ) ??
        false;
  }

  void _hydrate(ContentEditorState state) {
    if (!state.validated) return;
    final version = state.version,
        key = version == null ? null : '${version.id}:${version.revision}';
    if (state.saved != _saved) {
      _dirty = false;
      _file = null;
      _reviewed = false;
      _saved = state.saved;
    }
    if (version == null || key == _loadedKey || _dirty) return;
    _loadedKey = key;
    _hydrating = true;
    final selection = version.selection;
    _title.text = selection.title;
    _summary.text = selection.summary ?? '';
    _markdown.text = version.markdown ?? '';
    _context = _configuration.groups
        .where(
          (g) =>
              g.subjectId == selection.subjectId &&
              g.gradeLevelId == selection.gradeLevelId,
        )
        .firstOrNull;
    _groups = {...selection.groupIds};
    _topics = {...selection.topicIds};
    _hydrating = false;
    if (_configuration.sessionYearId != selection.sessionYearId) {
      _reloadConfiguration(selection.sessionYearId);
    }
  }

  Future<void> _reloadConfiguration(int year) async {
    try {
      final config = await widget.repository.configuration(year: year);
      if (!mounted || !widget.repository.isActive) return;
      setState(() => _configuration = config);
    } catch (error) {
      if (mounted) setState(() => _localError = contentFailure(error));
    }
  }

  ContentSelection? _selection() {
    final old = _cubit.state.version?.selection, g = _context;
    if ((g == null && old == null) ||
        _title.text.trim().length < 3 ||
        _groups.isEmpty) {
      setState(
        () => _localError = label(
          'Choose a teaching context, a title and at least one teaching group.',
        ),
      );
      return null;
    }
    return ContentSelection(
      subjectId: old?.subjectId ?? g!.subjectId,
      gradeLevelId: old?.gradeLevelId ?? g!.gradeLevelId,
      sessionYearId: old?.sessionYearId ?? _configuration.sessionYearId,
      title: _title.text.trim(),
      summary: _summary.text,
      groupIds: {..._groups},
      topicIds: {..._topics},
    );
  }

  Future<void> _pick() async {
    try {
      final file = await widget.pickFile();
      if (file != null && mounted && widget.repository.isActive) {
        setState(() => _file = file);
      }
    } catch (error) {
      if (mounted) setState(() => _localError = contentFailure(error));
    }
  }

  Future<void> _upload() async {
    final selection = _selection();
    if (selection == null) return;
    if (_file == null) {
      setState(
        () => _localError = label('Choose a private source file first.'),
      );
      return;
    }
    final savedBody = _cubit.state.version?.markdown;
    if (savedBody != null && _markdown.text != savedBody) {
      final replace = await showDialog<bool>(
        context: context,
        builder: (context) => AlertDialog(
          title: Text(label('Replace the source?')),
          content: Text(
            label(
              'Conversion replaces the unsaved Markdown edits on this screen. Save them first if you need to keep them.',
            ),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context, false),
              child: Text(label('Keep editing')),
            ),
            FilledButton(
              onPressed: () => Navigator.pop(context, true),
              child: Text(label('Replace source')),
            ),
          ],
        ),
      );
      if (replace != true || !mounted || !_foreground) return;
    }
    setState(() => _localError = null);
    if (_cubit.itemId == null) {
      await _cubit.create(selection, _file!);
    } else {
      await _cubit.replace(selection, _file!);
    }
  }

  Future<void> _save() async {
    final selection = _selection();
    if (selection == null) return;
    setState(() => _localError = null);
    await _cubit.save(selection, _markdown.text);
  }

  Future<void> _withdraw() async {
    final reason = await _reason(
      'Withdraw published material',
      'Record why this material is being withdrawn.',
    );
    if (reason != null) await _cubit.archive(reason);
  }

  Future<String?> _reason(String title, String help) async {
    final controller = TextEditingController();
    final result = await showDialog<String>(
      context: context,
      builder: (context) => StatefulBuilder(
        builder: (context, setDialog) => AlertDialog(
          title: Text(label(title)),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(label(help)),
              TextField(
                controller: controller,
                maxLength: 2000,
                minLines: 2,
                maxLines: 5,
                decoration: InputDecoration(labelText: label('Reason')),
                onChanged: (_) => setDialog(() => {}),
              ),
            ],
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context),
              child: Text(label('Cancel')),
            ),
            FilledButton(
              onPressed: controller.text.trim().length < 3
                  ? null
                  : () => Navigator.pop(context, controller.text.trim()),
              child: Text(label('Continue')),
            ),
          ],
        ),
      ),
    );
    WidgetsBinding.instance.addPostFrameCallback((_) => controller.dispose());
    return result;
  }

  Future<void> _publish() async {
    final preview = _cubit.state.preview;
    if (preview == null || !_reviewed) return;
    final reason = preview.requiresReason
        ? await _reason(
            'Leadership review reason',
            'Record why you are publishing another author\'s material.',
          )
        : null;
    if (preview.requiresReason && reason == null) return;
    await _cubit.publish(reviewed: true, reason: reason);
  }

  Future<void> _history(ContentHistory row) async {
    try {
      final version = await widget.repository.version(row.id);
      if (!mounted || !widget.repository.isActive || !_foreground) return;
      _historyOpen = true;
      await showDialog<void>(
        context: context,
        builder: (context) => Dialog(
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 900, maxHeight: 700),
            child: Column(
              children: [
                Padding(
                  padding: const EdgeInsets.all(16),
                  child: Row(
                    children: [
                      Expanded(
                        child: Text(
                          '${label('Version')} ${version.number} · ${version.selection.title}',
                        ),
                      ),
                      IconButton(
                        tooltip: label('Close'),
                        onPressed: () => Navigator.pop(context),
                        icon: const Icon(Icons.close),
                      ),
                    ],
                  ),
                ),
                Expanded(
                  child: SingleChildScrollView(
                    padding: const EdgeInsets.all(20),
                    child: ContentMarkdownView(
                      markdown:
                          version.markdown ??
                          label(
                            'No converted body is available for this version.',
                          ),
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
      );
      _historyOpen = false;
    } catch (error) {
      _historyOpen = false;
      if (mounted) setState(() => _localError = contentFailure(error));
    }
  }

  Future<void> _moreHistory(int item, int page) async {
    try {
      final data = await widget.repository.item(item, page: page);
      if (!mounted ||
          !_foreground ||
          !widget.repository.isActive ||
          data.id != item) {
        return;
      }
      _historyOpen = true;
      final selected = await showDialog<int>(
        context: context,
        builder: (context) => SimpleDialog(
          title: Text(label('More private versions')),
          children: [
            for (final row in data.versions)
              SimpleDialogOption(
                onPressed: () => Navigator.pop(context, row.id),
                child: Text('${label('Version')} ${row.number} · ${row.title}'),
              ),
            if (data.page < data.lastPage)
              SimpleDialogOption(
                onPressed: () => Navigator.pop(context, -1),
                child: Text(label('Load more')),
              ),
          ],
        ),
      );
      _historyOpen = false;
      if (!mounted || !_foreground || !widget.repository.isActive) return;
      if (selected == -1) {
        await _moreHistory(item, page + 1);
      } else if (selected != null) {
        await _history(data.versions.firstWhere((row) => row.id == selected));
      }
    } catch (error) {
      _historyOpen = false;
      if (mounted && _foreground) {
        setState(() => _localError = contentFailure(error));
      }
    }
  }

  Widget _field(
    TextEditingController controller,
    String name, {
    int lines = 1,
    bool enabled = true,
    int? maximum,
  }) => Padding(
    padding: const EdgeInsets.only(bottom: 16),
    child: TextField(
      controller: controller,
      enabled: enabled,
      minLines: lines,
      maxLines: lines == 1 ? 1 : null,
      maxLength: maximum,
      decoration: InputDecoration(
        labelText: label(name),
        border: const OutlineInputBorder(),
      ),
    ),
  );
  Widget _metadata(bool enabled, ContentVersion? version) {
    final subject = version?.selection.subjectId ?? _context?.subjectId,
        grade = version?.selection.gradeLevelId ?? _context?.gradeLevelId;
    final groups = _configuration.groups
        .where((g) => g.subjectId == subject && g.gradeLevelId == grade)
        .toList();
    final topics = _configuration.topics
        .where((t) => t.subjectId == subject && t.gradeLevelId == grade)
        .toList();
    final missing = _groups.difference(groups.map((g) => g.id).toSet());
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        if (version == null)
          Padding(
            padding: const EdgeInsets.only(bottom: 16),
            child: DropdownButtonFormField<int>(
              initialValue: _context?.id,
              isExpanded: true,
              decoration: InputDecoration(
                labelText: label('Teaching context'),
                border: const OutlineInputBorder(),
              ),
              items: [
                for (final g in _configuration.groups)
                  DropdownMenuItem(
                    value: g.id,
                    child: Text(
                      '${g.subjectName} · ${g.gradeName} · ${g.name}',
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
              ],
              onChanged: !enabled
                  ? null
                  : (id) {
                      setState(() {
                        _context = _configuration.groups.firstWhere(
                          (g) => g.id == id,
                        );
                        _groups = {id!};
                        _topics = {};
                        _dirty = true;
                        _reviewed = false;
                      });
                    },
            ),
          ),
        _field(_title, 'Content title', enabled: enabled, maximum: 191),
        _field(
          _summary,
          'Short summary',
          lines: 2,
          enabled: enabled,
          maximum: 2000,
        ),
        Text(
          label('Published teaching groups'),
          style: Theme.of(context).textTheme.titleMedium,
        ),
        for (final g in groups)
          CheckboxListTile(
            contentPadding: EdgeInsets.zero,
            title: Text(g.name),
            value: _groups.contains(g.id),
            onChanged: !enabled
                ? null
                : (checked) => setState(() {
                    checked == true ? _groups.add(g.id) : _groups.remove(g.id);
                    _dirty = true;
                    _reviewed = false;
                  }),
          ),
        for (final id in missing)
          Padding(
            padding: const EdgeInsets.only(bottom: 8),
            child: InputChip(
              label: Text(label('Unavailable teaching group')),
              onDeleted: !enabled
                  ? null
                  : () => setState(() {
                      _groups.remove(id);
                      _dirty = true;
                      _reviewed = false;
                    }),
            ),
          ),
        Text(
          label('Curriculum topic tags'),
          style: Theme.of(context).textTheme.titleMedium,
        ),
        if (topics.isEmpty)
          Padding(
            padding: const EdgeInsets.symmetric(vertical: 12),
            child: Text(
              label('No published topics yet. You may continue without tags.'),
            ),
          ),
        for (final t in topics)
          CheckboxListTile(
            contentPadding: EdgeInsets.zero,
            title: Text(t.name),
            subtitle: Text('${label('Version')} ${t.curriculumVersion}'),
            value: _topics.contains(t.id),
            onChanged: !enabled
                ? null
                : (checked) => setState(() {
                    checked == true ? _topics.add(t.id) : _topics.remove(t.id);
                    _dirty = true;
                    _reviewed = false;
                  }),
          ),
        for (final id in _topics.difference(topics.map((t) => t.id).toSet()))
          InputChip(
            label: Text(label('Unavailable curriculum topic')),
            onDeleted: !enabled
                ? null
                : () => setState(() {
                    _topics.remove(id);
                    _dirty = true;
                    _reviewed = false;
                  }),
          ),
      ],
    );
  }

  Widget _source(bool enabled) => Column(
    crossAxisAlignment: CrossAxisAlignment.stretch,
    children: [
      Text(
        _file?.name ?? label('No source selected'),
        overflow: TextOverflow.ellipsis,
      ),
      OutlinedButton.icon(
        onPressed: enabled ? _pick : null,
        icon: const Icon(Icons.upload_file_outlined),
        label: Text(label('Choose private source')),
      ),
      Text(
        label(
          'PDF, Word, slides, spreadsheet, text, audio or video up to 25 MiB. Audio/video must fit the school transcription allowance.',
        ),
      ),
      const SizedBox(height: 12),
      FilledButton(
        onPressed: enabled && _file != null ? _upload : null,
        child: Text(
          label(
            _cubit.itemId == null
                ? 'Upload for conversion'
                : 'Queue replacement source',
          ),
        ),
      ),
    ],
  );
  @override
  Widget build(BuildContext context) => PopScope(
    canPop: _allowLeave || (!_dirty && !_cubit.state.uncertain),
    onPopInvokedWithResult: (popped, _) async {
      if (!popped && await _leave() && mounted) {
        setState(() => _allowLeave = true);
        WidgetsBinding.instance.addPostFrameCallback((_) {
          if (mounted) Navigator.of(context).pop();
        });
      }
    },
    child: ScreenScaffold(
      title: label('Learning content'),
      body: BlocConsumer<ContentEditorCubit, ContentEditorState>(
        bloc: _cubit,
        listener: (context, state) {
          setState(() => _hydrate(state));
        },
        builder: (context, state) {
          if (!widget.repository.isActive) {
            return Center(
              child: ContentMessage(
                'Your account or school changed. Reopen Learning content.',
              ),
            );
          }
          if (!state.validated) {
            return state.busy
                ? const Center(child: CircularProgressIndicator())
                : Center(
                    child: ContentMessage(
                      state.error ??
                          'Connect to edit private learning material.',
                      retry: () => _cubit.load(),
                    ),
                  );
          }
          final version = state.version,
              ready = ['draft', 'published'].contains(version?.state.value),
              enabled =
                  state.writable &&
                  !state.busy &&
                  !state.uncertain &&
                  !state.conflict;
          return SingleChildScrollView(
            padding: const EdgeInsets.all(16),
            child: ContentColumn(
              maxWidth: 1200,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  if (state.busy) const LinearProgressIndicator(),
                  if (state.error != null || _localError != null)
                    ContentMessage(_localError ?? state.error!),
                  if (state.saved > 0)
                    Padding(
                      padding: const EdgeInsets.symmetric(vertical: 8),
                      child: Text(
                        label('Content change saved'),
                        style: TextStyle(
                          color: Theme.of(context).colorScheme.primary,
                        ),
                      ),
                    ),
                  if (state.uncertain)
                    Card(
                      child: Padding(
                        padding: const EdgeInsets.all(16),
                        child: Column(
                          children: [
                            Text(
                              label(
                                'The last request may have completed. Retry that same request or check your saved material before making another change.',
                              ),
                            ),
                            TextButton(
                              onPressed: state.busy
                                  ? null
                                  : () => _cubit.retryPending(),
                              child: Text(label('Retry previous request')),
                            ),
                          ],
                        ),
                      ),
                    ),
                  if (state.conflict)
                    Card(
                      child: Padding(
                        padding: const EdgeInsets.all(16),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.stretch,
                          children: [
                            Text(
                              label(
                                'Content changed. Your submitted draft is kept for comparison.',
                              ),
                            ),
                            ExpansionTile(
                              title: Text(label('Current saved draft')),
                              children: [
                                SelectableText(version?.markdown ?? ''),
                              ],
                            ),
                            TextButton(
                              onPressed: () => _cubit.acceptComparison(),
                              child: Text(
                                label(
                                  'Keep my draft and use current revisions',
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  Row(
                    children: [
                      Expanded(
                        child: Text(
                          version == null
                              ? label('New private upload')
                              : '${label('Version')} ${version.number} · ${label(contentState(version.state.value))}',
                          style: Theme.of(context).textTheme.titleLarge,
                        ),
                      ),
                      IconButton(
                        tooltip: label('Refresh content status'),
                        onPressed: state.busy || state.uncertain
                            ? null
                            : () => _cubit.load(keepConflict: _dirty),
                        icon: const Icon(Icons.refresh),
                      ),
                    ],
                  ),
                  if (!state.writable)
                    ContentMessage(
                      'This material is view only with your current allocation.',
                    ),
                  if (version != null)
                    Padding(
                      padding: const EdgeInsets.symmetric(vertical: 8),
                      child: Text(
                        '${label('Source file')}: ${version.source_.name} · ${label('Scan')}: ${label(version.source_.scanState.value)}',
                      ),
                    ),
                  if (version?.failureCode != null)
                    ContentMessage(
                      contentProcessingMessage(version!.failureCode!),
                    ),
                  if (version != null &&
                      [
                        'queued',
                        'scanning',
                        'converting',
                      ].contains(version.state.value))
                    ContentMessage(
                      'The private source is being scanned and converted. Refresh shortly. Nothing is published automatically.',
                    ),
                  if (version != null &&
                      ['failed', 'quarantined'].contains(version.state.value))
                    OutlinedButton(
                      onPressed: enabled && !_dirty
                          ? () => _cubit.retryConversion()
                          : null,
                      child: Text(label('Retry content conversion')),
                    ),
                  const SizedBox(height: 16),
                  LayoutBuilder(
                    builder: (context, constraints) {
                      final form = Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          _metadata(enabled, version),
                          if (ready) ...[
                            _field(
                              _markdown,
                              'Markdown draft',
                              lines: 12,
                              enabled: enabled,
                            ),
                            FilledButton(
                              onPressed: enabled && _dirty ? _save : null,
                              child: Text(label('Save reviewed draft')),
                            ),
                            const SizedBox(height: 12),
                            if (version!.state.value == 'draft')
                              OutlinedButton(
                                onPressed: enabled && !_dirty
                                    ? () => _cubit.preview()
                                    : null,
                                child: Text(label('Preview publication')),
                              ),
                            if (_dirty)
                              Text(
                                label(
                                  'Save your edits before opening the publication preview.',
                                ),
                              ),
                          ],
                          if (version == null) ...[
                            const SizedBox(height: 16),
                            _source(enabled),
                          ] else
                            ExpansionTile(
                              title: Text(label('Replace private source')),
                              children: [_source(enabled)],
                            ),
                        ],
                      );
                      final preview = Card(
                        child: Padding(
                          padding: const EdgeInsets.all(16),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.stretch,
                            children: [
                              Text(
                                label('Readable Markdown preview'),
                                style: Theme.of(context).textTheme.titleMedium,
                              ),
                              const SizedBox(height: 12),
                              ContentMarkdownView(
                                markdown: _markdown.text.isEmpty
                                    ? label(
                                        'The converted draft will appear here.',
                                      )
                                    : _markdown.text,
                              ),
                            ],
                          ),
                        ),
                      );
                      return constraints.maxWidth >= 900
                          ? Row(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Expanded(child: form),
                                const SizedBox(width: 16),
                                Expanded(child: preview),
                              ],
                            )
                          : Column(
                              crossAxisAlignment: CrossAxisAlignment.stretch,
                              children: [
                                form,
                                const SizedBox(height: 16),
                                preview,
                              ],
                            );
                    },
                  ),
                  if (state.preview != null && !_dirty)
                    Card(
                      child: Padding(
                        padding: const EdgeInsets.all(16),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.stretch,
                          children: [
                            Text(
                              label('Review content publication'),
                              style: Theme.of(context).textTheme.titleLarge,
                            ),
                            Text(
                              '${label('Current group members')}: ${state.preview!.learnerCount}',
                            ),
                            Text(
                              label(
                                'Check the text, topic tags and teaching groups above. Current membership and enrolment determine reader access.',
                              ),
                            ),
                            CheckboxListTile(
                              contentPadding: EdgeInsets.zero,
                              title: Text(
                                label(
                                  'I reviewed the text, topic tags and published teaching groups.',
                                ),
                              ),
                              value: _reviewed,
                              onChanged: enabled
                                  ? (v) =>
                                        setState(() => _reviewed = v ?? false)
                                  : null,
                            ),
                            FilledButton(
                              onPressed: enabled && _reviewed ? _publish : null,
                              child: Text(label('Publish reviewed content')),
                            ),
                          ],
                        ),
                      ),
                    ),
                  if (state.item != null) ...[
                    ExpansionTile(
                      title: Text(label('Private version history')),
                      children: [
                        for (final row in state.item!.versions)
                          ListTile(
                            title: Text(
                              '${label('Version')} ${row.number} · ${row.title}',
                            ),
                            subtitle: Text(
                              label(contentState(row.state.value)),
                            ),
                            onTap: () => _history(row),
                          ),
                        if (state.item!.page < state.item!.lastPage)
                          TextButton(
                            onPressed: state.busy
                                ? null
                                : () => _moreHistory(
                                    state.item!.id,
                                    state.item!.page + 1,
                                  ),
                            child: Text(label('More private versions')),
                          ),
                      ],
                    ),
                    if (state.item!.active)
                      OutlinedButton(
                        onPressed: enabled && !_dirty ? _withdraw : null,
                        child: Text(label('Withdraw published material')),
                      ),
                  ],
                ],
              ),
            ),
          );
        },
      ),
    ),
  );
}

String contentState(String state) =>
    const {
      'queued': 'Queued',
      'scanning': 'Scanning source',
      'converting': 'Converting source',
      'draft': 'Draft for review',
      'published': 'Published',
      'failed': 'Conversion needs attention',
      'quarantined': 'Quarantined for review',
      'superseded': 'Earlier draft',
    }[state] ??
    'Check content status';
String contentProcessingMessage(String code) =>
    const {
      'source_infected':
          'The source failed its malware scan. Upload a corrected file and contact the school.',
      'scanner_unavailable':
          'The private scanner is unavailable. Retry after the school restores it.',
      'scanner_definitions_outdated':
          'The scanner needs current definitions. Ask the school to update them, then retry.',
      'transcription_disabled':
          'Audio/video transcription is disabled for this school.',
      'transcription_daily_cap':
          'The school transcription allowance is used for today. Retry tomorrow or review the school controls.',
      'transcription_duration_limit':
          'The recording exceeds the remaining allowance. Upload a shorter recording or review the school controls.',
      'transcription_safeguarding_review':
          'A safeguarding guard quarantined this transcript. Review the source with the school.',
      'content_worker_interrupted':
          'The conversion worker was interrupted. Retry after its service is restored.',
      'source_or_authority_unavailable':
          'The source or author allocation is no longer available. Ask the school to review access.',
    }[code] ??
    'The source could not be processed safely. Check the file, retry after service restoration or contact the school.';
