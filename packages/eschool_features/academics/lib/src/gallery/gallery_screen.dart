import 'dart:async';
import 'dart:typed_data';
import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:uuid/uuid.dart';
import '../localization/academic_text.dart';
import '../wellbeing/report_photo_picker.dart';
import 'gallery_repository.dart';
import 'private_gallery_image.dart';

class PrivateGalleryScreen extends StatefulWidget {
  const PrivateGalleryScreen({
    required this.repository,
    this.family = false,
    this.learnerId,
    this.selectPhoto,
    super.key,
  });
  final PrivateGalleryRepository repository;
  final bool family;
  final int? learnerId;
  final Future<Uint8List?> Function()? selectPhoto;
  @override
  State<PrivateGalleryScreen> createState() => _PrivateGalleryScreenState();
}

class _PrivateGalleryScreenState extends State<PrivateGalleryScreen>
    with WidgetsBindingObserver {
  final _caption = TextEditingController(), _handle = TextEditingController();
  final _scroll = ScrollController();
  GalleryConfiguration? _configuration;
  List<GalleryTaggedPhoto> _tags = [];
  List<GalleryPhoto> _photos = [];
  int? _learner, _group, _year, _next, _fullPhoto;
  int _epoch = 0;
  bool _loading = true,
      _busy = false,
      _suspended = false,
      _picker = false,
      _pendingTab = false,
      _groupTab = false,
      _unknown = false;
  String? _error;
  Uint8List? _picked;
  _PhotoIntent? _intent;
  String l(String text) => academicText(context, text);
  bool get _active => mounted && !_suspended && widget.repository.isActive;
  bool _current(int epoch) => _active && epoch == _epoch;
  bool get _editable =>
      _active &&
      !_busy &&
      !_loading &&
      _intent == null &&
      _configuration?.canWrite == true;
  GalleryChild? get _child {
    for (final child in _configuration?.children ?? <GalleryChild>[]) {
      if (child.id == _learner) return child;
    }
    return null;
  }

  List<GalleryGroup> get _groups => (_configuration?.groups ?? <GalleryGroup>[])
      .where(
        (group) =>
            !widget.family ||
            (_learner != null && (group.learnerIds ?? []).contains(_learner)),
      )
      .toList();
  @override
  void initState() {
    super.initState();
    _learner = widget.learnerId;
    WidgetsBinding.instance.addObserver(this);
    unawaited(_load());
  }

  void _erase({bool draft = true, bool intent = true}) {
    _fullPhoto = null;
    _configuration = null;
    _photos = [];
    _tags = [];
    _next = null;
    if (draft) {
      _caption.clear();
      _handle.clear();
      _picked = null;
    }
    if (intent) {
      _intent = null;
      _unknown = false;
    }
  }

  @override
  void dispose() {
    _epoch++;
    _erase();
    WidgetsBinding.instance.removeObserver(this);
    _caption.dispose();
    _handle.dispose();
    _scroll.dispose();
    super.dispose();
  }

  @override
  void didUpdateWidget(PrivateGalleryScreen oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.repository != widget.repository ||
        oldWidget.family != widget.family ||
        oldWidget.learnerId != widget.learnerId) {
      _epoch++;
      _erase();
      _learner = widget.learnerId;
      _group = null;
      _year = null;
      unawaited(_load());
    }
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state != AppLifecycleState.resumed) {
      _epoch++;
      setState(() {
        _suspended = true;
        _erase(draft: !_picker, intent: false);
        if (_intent != null) _unknown = true;
        _busy = false;
      });
    } else {
      setState(() => _suspended = false);
      unawaited(_load(preserveDraft: _picker, preserveIntent: _intent != null));
    }
  }

  void _top() => WidgetsBinding.instance.addPostFrameCallback((_) {
    if (mounted && _scroll.hasClients) _scroll.jumpTo(0);
  });
  Future<void> _load({
    bool more = false,
    bool preserveDraft = false,
    bool preserveIntent = false,
  }) async {
    final epoch = ++_epoch, before = more ? _next : null;
    setState(() {
      _loading = true;
      _error = null;
      if (!more) _erase(draft: !preserveDraft, intent: !preserveIntent);
    });
    try {
      final config = await widget.repository.configuration();
      if (!_current(epoch)) return;
      if (widget.family) {
        final own = config.children.map((c) => c.id).toList();
        if (_learner == null && widget.learnerId == null && own.isNotEmpty)
          _learner = own.first;
        if (_learner == null || !own.contains(_learner))
          throw ApiException(
            'Your photo access changed. Reopen the gallery.',
            statusCode: 403,
            isApiMessage: true,
          );
      }
      if (_year != null && config.years.every((year) => year.id != _year))
        _year = null;
      _configuration = config;
      if (_groups.every((g) => g.id != _group))
        _group = _groups.isEmpty ? null : _groups.first.id;
      if (!widget.family || _groupTab) {
        final page = _group == null
            ? null
            : await widget.repository.group(_group!, before: before);
        if (!_current(epoch)) return;
        setState(() {
          _photos = [if (more) ..._photos, ...?page?.items];
          _tags = [];
          _next = page?.nextBefore;
        });
      } else {
        final page = await widget.repository.family(
          _learner!,
          pending: _pendingTab,
          year: _year,
          before: before,
        );
        if (!_current(epoch)) return;
        setState(() {
          _tags = [if (more) ..._tags, ...page.items];
          _photos = [];
          _next = page.nextBefore;
        });
      }
      setState(() => _loading = false);
    } catch (error) {
      if (!mounted || epoch != _epoch) return;
      setState(() {
        _erase();
        _loading = false;
        _error = error is ApiException && error.isApiMessage
            ? error.errorMessage
            : 'Could not open private photos. Refresh to check your access.';
      });
      _top();
    }
  }

  void _denied() {
    if (!mounted) return;
    _epoch++;
    setState(() {
      _erase();
      _error = 'Your photo access changed. Refresh the gallery.';
    });
  }

  Future<void> _pick() async {
    if (!_editable) return;
    final account = widget.repository, learner = _learner, group = _group;
    _picker = true;
    try {
      final bytes = await (widget.selectPhoto ?? selectReportPhoto)();
      if (!mounted ||
          !identical(account, widget.repository) ||
          !account.isActive ||
          learner != _learner ||
          group != _group)
        return;
      setState(() => _picked = bytes);
    } catch (error) {
      if (mounted)
        setState(
          () => _error = error is ApiException && error.isApiMessage
              ? error.errorMessage
              : 'Could not read this photo.',
        );
    } finally {
      _picker = false;
    }
  }

  Future<void> _write(
    String action,
    int id,
    Map<String, dynamic> data, {
    Uint8List? bytes,
  }) async {
    if (!_editable) return;
    if (action == 'upload' && widget.family) {
      _groupTab = false;
      _pendingTab = _child?.canApprove != true;
    }
    _intent = _PhotoIntent(action, id, {
      'operation_id': const Uuid().v4(),
      ...data,
    }, bytes);
    await _send();
  }

  Future<void> _send() async {
    final intent = _intent;
    if (intent == null || !_active || _busy || _loading) return;
    final epoch = _epoch;
    setState(() {
      _busy = true;
      _error = null;
    });
    _top();
    try {
      if (intent.bytes != null) {
        await widget.repository.upload(intent.payload, intent.bytes!);
      } else {
        await widget.repository.change(
          intent.action,
          intent.id,
          intent.payload,
        );
      }
      if (!_current(epoch)) return;
      setState(() {
        _busy = false;
        _intent = null;
        _unknown = false;
      });
      await _load();
    } catch (error) {
      if (!_current(epoch)) return;
      final uncertain =
          error is! ApiException ||
          error.isTransportFailure ||
          error.statusCode == null ||
          error.statusCode! >= 500;
      setState(() {
        _busy = false;
        _unknown = uncertain;
        if (!uncertain) _erase();
        _error = uncertain
            ? 'The result is unknown. Retry the same change before continuing.'
            : error.isApiMessage
            ? error.errorMessage
            : 'Photo access or consent changed. Refresh before continuing.';
      });
      _top();
    }
  }

  Widget _image(GalleryPhoto photo, {bool full = false}) => PrivateGalleryImage(
    identity:
        '${widget.repository.accountId}:${widget.repository.schoolId}:$_epoch:${photo.id}:${photo.revision}:$full',
    load: () => widget.repository.photo(photo.id, thumbnail: !full),
    active: () => _active && _configuration != null,
    label: photo.caption ?? l('Private photo'),
    full: full,
    onDenied: () =>
        WidgetsBinding.instance.addPostFrameCallback((_) => _denied()),
  );
  void _preview(GalleryPhoto photo) =>
      setState(() => _fullPhoto = _fullPhoto == photo.id ? null : photo.id);
  Widget _photo(GalleryPhoto photo, [GalleryTaggedPhoto? tag]) => Card(
    child: Padding(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          InkWell(
            onTap: _intent == null ? () => _preview(photo) : null,
            child: _image(photo, full: _fullPhoto == photo.id),
          ),
          if (photo.caption?.isNotEmpty == true) Text(photo.caption!),
          Text('${photo.capturedOn} · ${photo.schoolYear}'),
          if (tag != null && tag.retainedOnTransfer)
            Text(l('Retained family photo')),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: [
              if (tag != null && _pendingTab && _child?.canApprove == true)
                FilledButton(
                  onPressed: _editable
                      ? () => _write('decide', tag.tagId, {
                          'revision': tag.tagRevision,
                          'decision': 'approve',
                        })
                      : null,
                  child: Text(l('Approve photo tag')),
                ),
              if (tag != null)
                OutlinedButton(
                  onPressed: _editable
                      ? () => _write('remove', tag.tagId, {
                          'revision': tag.tagRevision,
                        })
                      : null,
                  child: Text(l('Remove my tag')),
                ),
              if (tag == null && widget.family && _learner != null)
                FilledButton(
                  onPressed: _editable
                      ? () => _write('propose', photo.id, {
                          'learner_id': _learner,
                        })
                      : null,
                  child: Text(l('Tag my learner')),
                ),
              if (tag == null && !widget.family) ...[
                OutlinedButton(
                  onPressed: _editable && _handle.text.trim().isNotEmpty
                      ? () => _write('propose', photo.id, {
                          'handle': _handle.text.trim(),
                        })
                      : null,
                  child: Text(l('Propose tag by handle')),
                ),
                TextButton(
                  onPressed: _editable
                      ? () => _write('withdraw', photo.id, {
                          'revision': photo.revision,
                        })
                      : null,
                  child: Text(l('Withdraw photo')),
                ),
              ],
            ],
          ),
        ],
      ),
    ),
  );
  @override
  Widget build(BuildContext context) {
    final configuration = _configuration;
    return ScreenScaffold(
      title: l('Private photos'),
      body: !_active
          ? Center(
              child: Text(l('Your photo access changed. Reopen the gallery.')),
            )
          : ListView(
              controller: _scroll,
              padding: const EdgeInsets.all(16),
              children: [
                if (_error != null) ...[
                  Text(
                    l(_error!),
                    style: TextStyle(
                      color: Theme.of(context).colorScheme.error,
                    ),
                  ),
                  const SizedBox(height: 8),
                ],
                if (_unknown)
                  FilledButton(
                    onPressed: !_busy && !_loading ? _send : null,
                    child: Text(l('Retry same change')),
                  ),
                if (_intent == null)
                  Align(
                    alignment: Alignment.centerLeft,
                    child: OutlinedButton(
                      onPressed: !_busy ? () => _load() : null,
                      child: Text(l('Refresh')),
                    ),
                  ),
                if (_loading || _busy) const LinearProgressIndicator(),
                if (configuration != null) ...[
                  Text(
                    l(
                      configuration.guardianRequired
                          ? 'Photo tags need guardian approval. Only approved tags appear in family history.'
                          : 'This school allows learners to approve their own photo tags.',
                    ),
                  ),
                  if (widget.family && widget.learnerId == null)
                    DropdownButtonFormField<int>(
                      initialValue: _learner,
                      isExpanded: true,
                      decoration: InputDecoration(labelText: l('Learner')),
                      items: configuration.children
                          .map(
                            (child) => DropdownMenuItem(
                              value: child.id,
                              child: Text(
                                '${child.name}${child.archived ? ' · ${l('History')}' : ''}',
                                overflow: TextOverflow.ellipsis,
                              ),
                            ),
                          )
                          .toList(),
                      onChanged: !_busy && _intent == null
                          ? (value) {
                              _learner = value;
                              _group = null;
                              unawaited(_load());
                            }
                          : null,
                    ),
                  if (widget.family)
                    Wrap(
                      spacing: 8,
                      children: [
                        ChoiceChip(
                          label: Text(l('Family history')),
                          selected: !_pendingTab && !_groupTab,
                          onSelected: !_busy && _intent == null
                              ? (_) {
                                  _pendingTab = false;
                                  _groupTab = false;
                                  unawaited(_load());
                                }
                              : null,
                        ),
                        ChoiceChip(
                          label: Text(l('Pending tags')),
                          selected: _pendingTab && !_groupTab,
                          onSelected: !_busy && _intent == null
                              ? (_) {
                                  _pendingTab = true;
                                  _groupTab = false;
                                  unawaited(_load());
                                }
                              : null,
                        ),
                        if (_groups.isNotEmpty)
                          ChoiceChip(
                            label: Text(l('My group photos')),
                            selected: _groupTab,
                            onSelected: !_busy && _intent == null
                                ? (_) {
                                    _groupTab = true;
                                    unawaited(_load());
                                  }
                                : null,
                          ),
                      ],
                    ),
                  if (!widget.family || _groupTab)
                    DropdownButtonFormField<int>(
                      key: ValueKey('group:$_group'),
                      initialValue: _group,
                      isExpanded: true,
                      decoration: InputDecoration(labelText: l('Group')),
                      items: _groups
                          .map(
                            (g) => DropdownMenuItem(
                              value: g.id,
                              child: Text(
                                g.name,
                                overflow: TextOverflow.ellipsis,
                              ),
                            ),
                          )
                          .toList(),
                      onChanged: !_busy && _intent == null
                          ? (value) {
                              _group = value;
                              unawaited(_load());
                            }
                          : null,
                    ),
                  if (widget.family && !_groupTab)
                    DropdownButtonFormField<int>(
                      key: ValueKey('year:$_year'),
                      initialValue: _year ?? 0,
                      isExpanded: true,
                      decoration: InputDecoration(labelText: l('School year')),
                      items: [
                        DropdownMenuItem(value: 0, child: Text(l('All years'))),
                        ...configuration.years.map(
                          (year) => DropdownMenuItem(
                            value: year.id,
                            child: Text(year.name),
                          ),
                        ),
                      ],
                      onChanged: !_busy && _intent == null
                          ? (value) {
                              _year = value == 0 ? null : value;
                              unawaited(_load());
                            }
                          : null,
                    ),
                  if (!widget.family)
                    TextField(
                      controller: _handle,
                      enabled: _editable,
                      maxLength: 21,
                      decoration: InputDecoration(
                        labelText: l('Approved learner handle'),
                      ),
                      onChanged: (_) => setState(() {}),
                    ),
                  if (configuration.canWrite &&
                      (widget.family && _child?.archived == false ||
                          !widget.family &&
                              _groups.any(
                                (g) => g.id == _group && g.canUpload,
                              ))) ...[
                    const Divider(),
                    Text(l('Upload a private photo')),
                    TextField(
                      controller: _caption,
                      enabled: _editable,
                      maxLength: 2000,
                      maxLines: 3,
                      decoration: InputDecoration(
                        labelText: l('Caption (optional)'),
                      ),
                    ),
                    Wrap(
                      spacing: 8,
                      children: [
                        OutlinedButton(
                          onPressed: _editable ? _pick : null,
                          child: Text(
                            l(
                              _picked == null
                                  ? 'Choose JPEG or PNG'
                                  : 'Photo selected',
                            ),
                          ),
                        ),
                        FilledButton(
                          onPressed: _editable && _picked != null
                              ? () => _write('upload', 0, {
                                  'scope': widget.family ? 'family' : 'group',
                                  if (widget.family)
                                    'learner_id': _learner
                                  else
                                    'activity_group_id': _group,
                                  'session_year_id':
                                      configuration.sessionYearId,
                                  'captured_on': configuration.today,
                                  'caption': _caption.text,
                                }, bytes: _picked)
                              : null,
                          child: Text(l('Upload photo')),
                        ),
                      ],
                    ),
                    Text(
                      l(
                        'Uploads use today’s date and the current school year. Photo files stay private.',
                      ),
                    ),
                    const Divider(),
                  ],
                  ..._tags.map((tag) => _photo(tag.photo, tag)),
                  ..._photos.map(_photo),
                  if (!_loading && _tags.isEmpty && _photos.isEmpty)
                    Padding(
                      padding: const EdgeInsets.all(24),
                      child: Text(l('No photos in this view.')),
                    ),
                  if (_next != null)
                    OutlinedButton(
                      onPressed: !_loading && !_busy && _intent == null
                          ? () => _load(more: true)
                          : null,
                      child: Text(l('Load more photos')),
                    ),
                ],
              ],
            ),
    );
  }
}

class _PhotoIntent {
  _PhotoIntent(
    this.action,
    this.id,
    Map<String, dynamic> data,
    Uint8List? photo,
  ) : payload = Map.unmodifiable(data),
      bytes = photo == null ? null : Uint8List.fromList(photo);
  final String action;
  final int id;
  final Map<String, dynamic> payload;
  final Uint8List? bytes;
}
