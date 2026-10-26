import 'dart:async';
import 'dart:convert';
import 'dart:typed_data';

import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:uuid/uuid.dart';

import '../localization/academic_text.dart';
import '../tutor/tutor_repository.dart';
import '../tutor/tutor_working_crop.dart';
import '../wellbeing/private_report_image.dart';
import '../wellbeing/report_photo_picker.dart';
import 'teaching_draft_editor.dart';
import 'teaching_repository.dart';

String teachingKindLabel(String kind) => switch (kind) {
  'lesson' => 'Lesson materials',
  'worksheet' => 'Three-level worksheet',
  'practice' => 'Practice questions',
  'report_comment' => 'Report comment',
  'script_marks' => 'Script proposals',
  'reteach' => 'Re-teach pack',
  'follow_up_quiz' => 'Follow-up quiz',
  _ => 'Teaching draft',
};
String teachingStateLabel(String state) => switch (state) {
  'ready' => 'Ready for review',
  'approved' => 'Approved',
  'published' => 'Published',
  'applied' => 'Official marks recorded',
  'discarded' => 'Discarded',
  'pending' => 'Pending',
  'quarantined' => 'Withheld for safeguarding',
  _ => 'Unavailable',
};

/// Private online staff workspace. No learner text, image or unsent body is cached.
class TeachingScreen extends StatefulWidget {
  const TeachingScreen({
    required this.repository,
    this.insightsOnly = false,
    this.canRead,
    this.canWrite,
    this.selectPhoto,
    this.selectCameraPhoto,
    this.exportPdf,
    super.key,
  });
  final TeachingRepository repository;
  final bool insightsOnly;
  final bool Function()? canRead, canWrite;
  final Future<Uint8List?> Function()? selectPhoto, selectCameraPhoto;
  final Future<void> Function(Uint8List, String)? exportPdf;
  @override
  State<TeachingScreen> createState() => _TeachingScreenState();
}

class _TeachingScreenState extends State<TeachingScreen>
    with WidgetsBindingObserver {
  final _brief = TextEditingController();
  TeachingDraftConfiguration? _configuration;
  TeachingDraftChoices? _choices;
  TeachingLearnerPage? _learners;
  TeachingDraftPage? _drafts;
  TeachingDraftDetail? _detail;
  TaskInsightDetail? _insight;
  TeachingReviewIntent? _pendingReview;
  TeachingPhotoIntent? _pendingUpload;
  final _photos = <TeachingPhotoReceipt>[];
  final _photoBytes = <String, Uint8List>{};
  final _topics = <int>{};
  Uint8List? _picked;
  String? _generationOperation, _draftLocator, _photoOperation;
  String _cropScope = const Uuid().v4();
  String _kind = 'worksheet', _language = 'en', _error = '';
  int? _group, _source, _learner, _task;
  int _epoch = 0, _learnerPage = 1;
  bool _busy = true, _suspended = false, _dead = false;
  bool _creating = false, _confirmed = false;
  bool _picking = false;

  String l(String value) => academicText(context, value);
  bool get _active =>
      mounted &&
      !_dead &&
      !_suspended &&
      widget.repository.isActive &&
      (widget.canRead?.call() ?? true);
  bool get _writable =>
      _active &&
      (_configuration?.writable ?? false) &&
      (widget.canWrite?.call() ?? true);
  bool _current(int epoch) => _active && epoch == _epoch;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    unawaited(_reload());
  }

  @override
  void didUpdateWidget(TeachingScreen oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (!identical(oldWidget.repository, widget.repository) ||
        oldWidget.insightsOnly != widget.insightsOnly) {
      _epoch++;
      _erase();
      unawaited(_reload());
    }
  }

  void _erase({bool locators = true}) {
    _brief.clear();
    _configuration = null;
    _choices = null;
    _learners = null;
    _drafts = null;
    _detail = null;
    _insight = null;
    _pendingReview = null;
    _pendingUpload = null;
    _photoBytes.clear();
    _picked = null;
    _confirmed = false;
    _cropScope = const Uuid().v4();
    if (locators) {
      _photos.clear();
      _topics.clear();
      _source = _learner = _task = null;
      _creating = false;
      _photoOperation = null;
      _learnerPage = 1;
      _generationOperation = _draftLocator = null;
      _group = null;
    }
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state != AppLifecycleState.resumed) {
      _epoch++;
      setState(() {
        _suspended = true;
        _busy = false;
        _erase(locators: false);
      });
    } else if (_suspended) {
      setState(() => _suspended = false);
      if (!_picking) unawaited(_reload());
    }
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    _epoch++;
    _erase();
    _brief.dispose();
    super.dispose();
  }

  Future<void> _perform(Future<void> Function(int) action) async {
    if (!_active) return;
    final epoch = ++_epoch;
    setState(() {
      _busy = true;
      _error = '';
    });
    try {
      await action(epoch);
    } catch (error) {
      if (!_current(epoch)) return;
      setState(() {
        if (error is ApiException &&
            const [401, 403, 404].contains(error.statusCode)) {
          _dead = true;
          _erase();
          _error = 'Reopen the teaching workspace to check your access.';
        } else {
          _error = error is ApiException && error.isApiMessage
              ? error.errorMessage
              : error is ApiException && error.statusCode == 422
              ? 'Review the teaching fields and try again.'
              : error is ApiException && error.statusCode == 409
              ? 'This draft or its evidence changed. Reload before reviewing it.'
              : 'The request was not confirmed. Check the saved request before sending another.';
          if (error is ApiException &&
              const [409, 422, 429].contains(error.statusCode)) {
            _pendingReview = null;
          }
        }
      });
    } finally {
      if (_current(epoch)) setState(() => _busy = false);
    }
  }

  Future<void> _reload() => _perform((epoch) async {
    final config = await widget.repository.configuration(
      insights: widget.insightsOnly,
    );
    if (!_current(epoch)) return;
    final group = config.groups.any((row) => row.id == _group)
        ? _group
        : config.groups.firstOrNull?.id;
    setState(() {
      if (_group != group) {
        _photos.clear();
        _photoBytes.clear();
        _topics.clear();
        _source = _learner = _task = null;
        _photoOperation = _generationOperation = _draftLocator = null;
        _learnerPage = 1;
      }
      _configuration = config;
      _group = group;
    });
    if (group != null) await _loadGroup(epoch, group);
    if (!_current(epoch)) return;
    for (final reference in List.of(_photos)) {
      final bytes = await widget.repository.photo(reference);
      if (!_current(epoch)) return;
      setState(() => _photoBytes[reference.id] = bytes);
    }
    if (_generationOperation != null) {
      final result = await widget.repository.receipt(_generationOperation!);
      if (!_current(epoch)) return;
      setState(() {
        _detail = result;
        _draftLocator = result.id;
        _generationOperation = null;
      });
    } else if (_draftLocator != null) {
      final result = await widget.repository.read(_draftLocator!);
      if (!_current(epoch)) return;
      setState(() => _detail = result);
    }
  });

  Future<void> _loadGroup(
    int epoch,
    int group, {
    int? learnerPage,
    int draftPage = 1,
  }) async {
    final choices = await widget.repository.choices(
      group,
      insights: widget.insightsOnly,
    );
    if (!_current(epoch)) return;
    TeachingLearnerPage? learners;
    TeachingDraftPage? drafts;
    if (!widget.insightsOnly) {
      learners = await widget.repository.learners(
        group,
        page: learnerPage ?? _learnerPage,
      );
      if (!_current(epoch)) return;
      drafts = await widget.repository.drafts(group, page: draftPage);
      if (!_current(epoch)) return;
    }
    setState(() {
      _choices = choices;
      _learners = learners;
      _drafts = drafts;
      if (!choices.sources.any((row) => row.id == _source))
        _source = choices.sources.firstOrNull?.id;
      if (!choices.tasks.any((row) => row.id == _task)) _task = null;
      _topics.removeWhere((id) => !choices.topics.any((row) => row.id == id));
      if (learners != null) {
        _learnerPage = learners.page;
        if (!learners.items.any((row) => row.id == _learner && row.approved)) {
          _learner = null;
          _photos.clear();
          _photoBytes.clear();
          _photoOperation = null;
        }
      }
    });
  }

  Future<void> _switchGroup(int group) => _perform((epoch) async {
    setState(() {
      _group = group;
      _detail = null;
      _draftLocator = null;
      _insight = null;
      _choices = null;
      _learners = null;
      _drafts = null;
      _source = _learner = _task = null;
      _photos.clear();
      _photoBytes.clear();
      _topics.clear();
      _brief.clear();
      _photoOperation = null;
      _learnerPage = 1;
      _creating = false;
    });
    await _loadGroup(epoch, group);
  });

  Future<void> _open(String id) => _perform((epoch) async {
    final result = await widget.repository.read(id);
    if (!_current(epoch)) return;
    setState(() {
      _detail = result;
      _draftLocator = id;
      _confirmed = false;
      _creating = false;
      _pendingReview = null;
    });
  });

  Future<void> _generate() async {
    if (!_writable ||
        _group == null ||
        _source == null ||
        _generationOperation != null) {
      return;
    }
    final input = TeachingDraftGeneration.fromJson({
      'operation_id': _photoOperation ?? const Uuid().v4(),
      'content_version_id': _source,
      'kind': _kind,
      'language': _language,
      'brief': _brief.text.trim(),
      'learner_id': const {'report_comment', 'script_marks'}.contains(_kind)
          ? _learner
          : null,
      'task_id':
          const {'script_marks', 'reteach', 'follow_up_quiz'}.contains(_kind)
          ? _task
          : null,
      'photo_ids': _kind == 'script_marks'
          ? _photos.map((row) => row.id).toList()
          : <String>[],
      'topic_ids': const {'practice', 'follow_up_quiz'}.contains(_kind)
          ? _topics.toList()
          : <int>[],
    });
    final intent = TeachingGenerationIntent(_group!, input);
    _generationOperation = intent.operation;
    await _perform((epoch) async {
      final result = await widget.repository.generate(intent);
      if (!_current(epoch)) return;
      setState(() {
        _detail = result;
        _draftLocator = result.id;
        _generationOperation = null;
        _creating = false;
        _brief.clear();
        _photos.clear();
        _photoBytes.clear();
        _photoOperation = null;
      });
    });
  }

  Future<void> _recover() => _perform((epoch) async {
    final operation = _generationOperation;
    if (operation == null) return;
    final result = await widget.repository.receipt(operation);
    if (!_current(epoch)) return;
    setState(() {
      _detail = result;
      _draftLocator = result.id;
      _generationOperation = null;
      _creating = false;
      _brief.clear();
    });
  });

  Future<void> _review(
    String action, {
    TeachingDraftDefinition? definition,
  }) async {
    if (!_writable || _detail == null || _pendingReview != null) return;
    final intent = TeachingReviewIntent(
      _detail!.id,
      TeachingDraftReview.fromJson({
        'operation_id': const Uuid().v4(),
        'expected_revision': _detail!.revision,
        'action': action,
        'definition': definition?.toJson(),
        'confirmed':
            const {'publish_practice', 'confirm_marks'}.contains(action) &&
            _confirmed,
      }),
    );
    _pendingReview = intent;
    await _sendReview(intent);
  }

  Future<void> _sendReview(TeachingReviewIntent intent) =>
      _perform((epoch) async {
        await widget.repository.review(intent);
        if (!_current(epoch)) return;
        final fresh = await widget.repository.read(intent.draft);
        if (!_current(epoch)) return;
        setState(() {
          _detail = fresh;
          _pendingReview = null;
          _confirmed = false;
        });
      });

  Future<void> _pick({bool camera = false}) async {
    if (!_writable || _group == null || _learner == null || _picking) return;
    final repository = widget.repository;
    FocusManager.instance.primaryFocus?.unfocus();
    final group = _group, learner = _learner, source = _source;
    _picking = true;
    setState(() => _busy = true);
    try {
      final bytes = await (camera
          ? widget.selectCameraPhoto!
          : widget.selectPhoto ?? selectReportPhoto)();
      if (!mounted ||
          !identical(repository, widget.repository) ||
          !repository.isActive ||
          _group != group ||
          (WidgetsBinding.instance.lifecycleState != null &&
              WidgetsBinding.instance.lifecycleState !=
                  AppLifecycleState.resumed))
        return;
      _suspended = false;
      await _reload();
      if (!_writable ||
          _group != group ||
          _learner != learner ||
          _source != source ||
          bytes == null)
        return;
      setState(() {
        _creating = true;
        _picked = Uint8List.fromList(bytes);
        _photoOperation ??= const Uuid().v4();
      });
    } catch (_) {
      if (_active)
        setState(() => _error = 'Choose a JPEG or PNG photo up to 4 MB.');
    } finally {
      _picking = false;
      if (_active) setState(() => _busy = false);
    }
  }

  Future<void> _upload(TutorPhotoIntent crop) async {
    if (!_writable || _group == null || _learner == null) return;
    final intent = TeachingPhotoIntent(_group!, _learner!, crop);
    setState(() {
      _picked = null;
      _pendingUpload = intent;
    });
    await _sendUpload(intent);
  }

  Future<void> _sendUpload(TeachingPhotoIntent intent) =>
      _perform((epoch) async {
        final result = await widget.repository.upload(intent);
        if (!_current(epoch)) return;
        final preview = await widget.repository.photo(result);
        if (!_current(epoch)) return;
        setState(() {
          if (!_photos.any((row) => row.id == result.id)) _photos.add(result);
          _photoBytes[result.id] = preview;
          _pendingUpload = null;
        });
      });

  Future<void> _removePhoto(TeachingPhotoReceipt photo) =>
      _perform((epoch) async {
        await widget.repository.revoke(photo.id, const Uuid().v4());
        if (!_current(epoch)) return;
        setState(() {
          _photos.removeWhere((row) => row.id == photo.id);
          _photoBytes.remove(photo.id);
        });
      });

  Future<void> _readInsights() => _perform((epoch) async {
    if (_group == null || _task == null) return;
    final result = await widget.repository.insights(_group!, _task!);
    if (!_current(epoch)) return;
    setState(() => _insight = result);
  });

  Widget _select(
    String label,
    int? value,
    List<({int id, String name})> items,
    ValueChanged<int?> change, {
    bool enabled = true,
  }) => Padding(
    padding: const EdgeInsets.only(bottom: 12),
    child: DropdownButtonFormField<int>(
      key: ValueKey(
        'teaching-$label-${items.map((row) => row.id).join('-')}-$value',
      ),
      initialValue: items.any((row) => row.id == value) ? value : null,
      isExpanded: true,
      decoration: InputDecoration(labelText: l(label)),
      items: items
          .map(
            (row) => DropdownMenuItem(
              value: row.id,
              child: Text(
                row.name,
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
              ),
            ),
          )
          .toList(),
      onChanged: !_busy && enabled ? change : null,
    ),
  );

  Widget _create() {
    final choices = _choices!;
    final personal = const {'report_comment', 'script_marks'}.contains(_kind);
    final needsTask = const {
      'script_marks',
      'reteach',
      'follow_up_quiz',
    }.contains(_kind);
    final practice = const {'practice', 'follow_up_quiz'}.contains(_kind);
    final labels = {
      'lesson': 'Lesson materials',
      'worksheet': 'Three-level worksheet',
      'practice': 'Practice questions',
      'report_comment': 'Report comment',
      'script_marks': 'Script proposals',
      'reteach': 'Re-teach pack',
      'follow_up_quiz': 'Follow-up quiz',
    };
    final approvedLearner =
        _learners?.items.any((row) => row.id == _learner && row.approved) ??
        false;
    final valid =
        _pendingUpload == null &&
        _source != null &&
        _brief.text.trim().length >= 3 &&
        (!personal || approvedLearner) &&
        (!needsTask || _task != null) &&
        (!practice || _topics.isNotEmpty) &&
        (_kind != 'script_marks' || _photos.isNotEmpty);
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        const AcademicText(
          'Every result remains a private draft until you review it.',
        ),
        const SizedBox(height: 12),
        DropdownButtonFormField<String>(
          initialValue: _kind,
          isExpanded: true,
          decoration: InputDecoration(labelText: l('Draft type')),
          items: labels.entries
              .map(
                (row) =>
                    DropdownMenuItem(value: row.key, child: Text(l(row.value))),
              )
              .toList(),
          onChanged: !_busy && (_photos.isEmpty && _pendingUpload == null)
              ? (value) => setState(() {
                  _kind = value!;
                  _confirmed = false;
                })
              : null,
        ),
        const SizedBox(height: 12),
        _select(
          'Published lesson',
          _source,
          choices.sources.map((row) => (id: row.id, name: row.name)).toList(),
          (value) => setState(() => _source = value),
        ),
        DropdownButtonFormField<String>(
          initialValue: _language,
          decoration: InputDecoration(labelText: l('Draft language')),
          items: const [
            DropdownMenuItem(value: 'en', child: Text('English')),
            DropdownMenuItem(value: 'af', child: Text('Afrikaans')),
            DropdownMenuItem(value: 'zu', child: Text('isiZulu')),
            DropdownMenuItem(value: 'ar', child: Text('العربية')),
          ],
          onChanged: !_busy
              ? (value) => setState(() => _language = value!)
              : null,
        ),
        const SizedBox(height: 12),
        if (personal) ...[
          _select(
            'Learner',
            _learner,
            (_learners?.items ?? [])
                .where((row) => row.approved)
                .map((row) => (id: row.id, name: row.name))
                .toList(),
            (value) => setState(() => _learner = value),
            enabled: (_photos.isEmpty && _pendingUpload == null),
          ),
          if ((_learners?.lastPage ?? 1) > 1)
            Wrap(
              spacing: 8,
              children: [
                Text(
                  '${l('Page')} ${_learners!.page} / ${_learners!.lastPage}',
                ),
                TextButton(
                  onPressed:
                      !_busy &&
                          (_photos.isEmpty && _pendingUpload == null) &&
                          _learners!.page > 1
                      ? () => _perform(
                          (epoch) => _loadGroup(
                            epoch,
                            _group!,
                            learnerPage: _learners!.page - 1,
                          ),
                        )
                      : null,
                  child: const AcademicText('Previous'),
                ),
                TextButton(
                  onPressed:
                      !_busy &&
                          (_photos.isEmpty && _pendingUpload == null) &&
                          _learners!.page < _learners!.lastPage
                      ? () => _perform(
                          (epoch) => _loadGroup(
                            epoch,
                            _group!,
                            learnerPage: _learners!.page + 1,
                          ),
                        )
                      : null,
                  child: const AcademicText('Next'),
                ),
              ],
            ),
        ],
        if (needsTask)
          _select(
            'Assessment task',
            _task,
            choices.tasks.map((row) => (id: row.id, name: row.name)).toList(),
            (value) => setState(() => _task = value),
          ),
        if (practice) ...[
          const AcademicText('Choose up to five curriculum topics'),
          Wrap(
            spacing: 8,
            children: choices.topics
                .map(
                  (row) => FilterChip(
                    label: Text(row.name),
                    selected: _topics.contains(row.id),
                    onSelected: !_busy
                        ? (selected) => setState(() {
                            if (selected && _topics.length < 5) {
                              _topics.add(row.id);
                            }
                            if (!selected) _topics.remove(row.id);
                          })
                        : null,
                  ),
                )
                .toList(),
          ),
        ],
        const SizedBox(height: 12),
        TextField(
          key: const ValueKey('teaching-brief'),
          controller: _brief,
          enabled: !_busy,
          minLines: 2,
          maxLines: 5,
          maxLength: 1500,
          decoration: InputDecoration(
            labelText: l('What should this draft help with?'),
          ),
          onChanged: (_) => setState(() {}),
        ),
        if (_kind == 'script_marks') ...[
          const AcademicText(
            'Photograph working only. Exclude names and other learners before approving the crop.',
          ),
          Wrap(
            spacing: 8,
            children: [
              OutlinedButton.icon(
                onPressed:
                    !_busy &&
                        _pendingUpload == null &&
                        approvedLearner &&
                        _photos.length < 3
                    ? () => _pick()
                    : null,
                icon: const Icon(Icons.photo_library_outlined),
                label: const AcademicText('Choose working photo'),
              ),
              if (widget.selectCameraPhoto != null)
                OutlinedButton.icon(
                  onPressed:
                      !_busy &&
                          _pendingUpload == null &&
                          approvedLearner &&
                          _photos.length < 3
                      ? () => _pick(camera: true)
                      : null,
                  icon: const Icon(Icons.camera_alt_outlined),
                  label: const AcademicText('Photograph working'),
                ),
            ],
          ),
          if (_pendingUpload != null)
            Wrap(
              spacing: 8,
              children: [
                FilledButton(
                  onPressed: !_busy && _writable
                      ? () => _sendUpload(_pendingUpload!)
                      : null,
                  child: const AcademicText('Retry approved photo upload'),
                ),
                TextButton(
                  onPressed: !_busy
                      ? () => setState(() {
                          _pendingUpload = null;
                          if (_photos.isEmpty) _photoOperation = null;
                        })
                      : null,
                  child: const AcademicText('Remove unsent photo'),
                ),
              ],
            ),
          for (final photo in _photos)
            Column(
              children: [
                if (_photoBytes[photo.id] case final Uint8List bytes)
                  PrivateReportImage(
                    base64: base64Encode(bytes),
                    label: l('Approved working crop'),
                    maximumEdge: 768,
                    maximumEncodedLength: 1398104,
                  ),
                TextButton(
                  onPressed: !_busy ? () => _removePhoto(photo) : null,
                  child: const AcademicText('Remove photo'),
                ),
              ],
            ),
        ],
        const SizedBox(height: 16),
        FilledButton.icon(
          key: const ValueKey('teaching-generate'),
          onPressed:
              !_busy && _writable && valid && _generationOperation == null
              ? _generate
              : null,
          icon: const Icon(Icons.auto_awesome_outlined),
          label: const AcademicText('Create private draft'),
        ),
      ],
    );
  }

  Widget _draftDetail(TeachingDraftDetail draft) => Column(
    crossAxisAlignment: CrossAxisAlignment.stretch,
    children: [
      Text(
        '${l('Revision')} ${draft.revision} · ${l(teachingStateLabel(draft.state.value))}',
      ),
      if (draft.definition != null)
        TeachingDraftEditor(
          key: ValueKey('${draft.id}:${draft.revision}'),
          draft: draft,
          busy: _busy || _pendingReview != null,
          writable:
              _writable &&
              const {'ready', 'approved'}.contains(draft.state.value),
          onEdit: (definition) => _review('edit', definition: definition),
          onApprove: () => _review('approve'),
        )
      else
        Padding(
          padding: const EdgeInsets.symmetric(vertical: 16),
          child: AcademicText(
            draft.state.value == 'quarantined'
                ? 'This draft is withheld for safeguarding review.'
                : draft.state.value == 'pending'
                ? 'This request is still pending. Check its saved result.'
                : 'A usable draft was not confirmed. Your school learning material remains available.',
          ),
        ),
      if (draft.state.value == 'approved' &&
          const {
            'practice',
            'follow_up_quiz',
            'script_marks',
          }.contains(draft.kind.value)) ...[
        CheckboxListTile(
          value: _confirmed,
          controlAffinity: ListTileControlAffinity.leading,
          onChanged: !_busy && _writable && _pendingReview == null
              ? (value) => setState(() => _confirmed = value ?? false)
              : null,
          title: AcademicText(
            draft.kind.value == 'script_marks'
                ? 'I reviewed every proposed question mark and want to record official marks.'
                : 'I reviewed these questions and want to publish them to practice.',
          ),
        ),
        FilledButton(
          onPressed:
              !_busy &&
                  _writable &&
                  _confirmed &&
                  _pendingReview == null &&
                  (draft.kind.value != 'script_marks' ||
                      draft.definition!.questionMarks!.every(
                        (row) => row.mark != null,
                      ))
              ? () => _review(
                  draft.kind.value == 'script_marks'
                      ? 'confirm_marks'
                      : 'publish_practice',
                )
              : null,
          child: AcademicText(
            draft.kind.value == 'script_marks'
                ? 'Confirm official marks'
                : 'Publish to practice',
          ),
        ),
      ],
      if (const {
            'approved',
            'published',
            'applied',
          }.contains(draft.state.value) &&
          widget.exportPdf != null)
        Wrap(
          spacing: 8,
          children: [
            OutlinedButton(
              onPressed: !_busy ? () => _export(draft.id) : null,
              child: const AcademicText('Export reviewed copy'),
            ),
            if (const {'practice', 'follow_up_quiz'}.contains(draft.kind.value))
              OutlinedButton(
                onPressed: !_busy
                    ? () => _export(draft.id, answers: true)
                    : null,
                child: const AcademicText('Export teacher answer copy'),
              ),
          ],
        ),
      if (const {
        'pending',
        'failed',
        'ready',
        'approved',
      }.contains(draft.state.value))
        TextButton(
          onPressed: !_busy && _writable && _pendingReview == null
              ? () => _review('discard')
              : null,
          child: const AcademicText('Discard draft'),
        ),
      if (draft.state.value == 'pending')
        TextButton(
          onPressed: !_busy ? () => _open(draft.id) : null,
          child: const AcademicText('Check saved draft'),
        ),
    ],
  );

  Future<void> _export(String id, {bool answers = false}) => _perform((
    epoch,
  ) async {
    final bytes = await widget.repository.pdf(id, answers: answers);
    if (!_current(epoch)) return;
    await widget.exportPdf!(
      bytes,
      '${answers ? 'teacher-answer-copy' : 'reviewed-teaching-copy'}-$id-r${_detail?.revision ?? 0}.pdf',
    );
  });

  @override
  Widget build(BuildContext context) {
    final valid = _active;
    final body = !valid
        ? Center(
            child: AcademicText(
              _suspended
                  ? 'Rechecking your teaching access.'
                  : 'Reopen the teaching workspace to check your access.',
            ),
          )
        : _picked != null
        ? SingleChildScrollView(
            child: TutorWorkingCrop(
              key: ValueKey(_cropScope),
              bytes: _picked!,
              session: _cropScope,
              operation: _photoOperation!,
              selectionTitle: 'Select only the learner’s working',
              selectionGuidance:
                  'Leave names and other learners outside the selection.',
              approvalLabel:
                  'I checked this selection contains only the learner’s working.',
              onApproved: (value) => _upload(value),
              onCancel: () => setState(() => _picked = null),
            ),
          )
        : ListView(
            padding: const EdgeInsets.all(16),
            children: [
              if (_busy) const LinearProgressIndicator(),
              if (_error.isNotEmpty)
                Padding(
                  padding: const EdgeInsets.symmetric(vertical: 12),
                  child: AcademicText(_error),
                ),
              if (_pendingReview != null)
                FilledButton(
                  onPressed: !_busy && _writable
                      ? () => _sendReview(_pendingReview!)
                      : null,
                  child: const AcademicText('Recover original review'),
                ),
              if (_generationOperation != null)
                FilledButton(
                  onPressed: !_busy ? _recover : null,
                  child: const AcademicText('Check saved request'),
                ),
              if (_configuration != null) ...[
                if (_configuration!.groups.isEmpty)
                  const AcademicText(
                    'No current teaching groups are available.',
                  ),
                _select(
                  'Teaching group',
                  _group,
                  _configuration!.groups
                      .map(
                        (row) => (
                          id: row.id,
                          name: '${row.name} · ${row.subjectName}',
                        ),
                      )
                      .toList(),
                  (value) {
                    if (value != null) unawaited(_switchGroup(value));
                  },
                  enabled:
                      _generationOperation == null &&
                      _pendingReview == null &&
                      (_photos.isEmpty && _pendingUpload == null),
                ),
              ],
              if (_choices != null && widget.insightsOnly) ...[
                _select(
                  'Assessment task',
                  _task,
                  _choices!.tasks
                      .map((row) => (id: row.id, name: row.name))
                      .toList(),
                  (value) => setState(() {
                    _task = value;
                    _insight = null;
                  }),
                ),
                FilledButton(
                  onPressed: !_busy && _task != null ? _readInsights : null,
                  child: const AcademicText('View test insights'),
                ),
                if (_insight != null) ...[
                  const SizedBox(height: 16),
                  if (!_insight!.summary.hasQuestionEvidence)
                    const AcademicText(
                      'No question evidence yet. Missing marks are unknown.',
                    ),
                  for (final question in _insight!.summary.questions)
                    Card(
                      child: Padding(
                        padding: const EdgeInsets.all(12),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              '${l('Question')} ${question.number}',
                              style: Theme.of(context).textTheme.titleMedium,
                            ),
                            Text(
                              '${l('Missed')} ${question.missedCount} · ${l('Evidence')} ${question.evidenceCount} · ${l('Unknown')} ${question.unknownCount}',
                            ),
                            Text(
                              '${l('Average')} ${question.averageMark ?? l('Unknown')} / ${question.maxMark}',
                            ),
                          ],
                        ),
                      ),
                    ),
                  const AcademicText(
                    'Use the Teaching assistant to draft a re-teach pack or follow-up quiz from this task.',
                  ),
                ],
              ],
              if (_choices != null && !widget.insightsOnly) ...[
                Wrap(
                  spacing: 8,
                  children: [
                    OutlinedButton(
                      onPressed:
                          !_busy &&
                              _writable &&
                              _generationOperation == null &&
                              _pendingReview == null &&
                              _pendingUpload == null
                          ? () => setState(() {
                              _creating = true;
                              _detail = null;
                              _draftLocator = null;
                              _confirmed = false;
                              _brief.clear();
                              _photos.clear();
                              _photoBytes.clear();
                              _photoOperation = null;
                            })
                          : null,
                      child: const AcademicText('New draft'),
                    ),
                    TextButton(
                      onPressed:
                          !_busy &&
                              _generationOperation == null &&
                              _pendingReview == null &&
                              _pendingUpload == null
                          ? _reload
                          : null,
                      child: const AcademicText('Refresh'),
                    ),
                  ],
                ),
                if (_creating && _generationOperation == null) _create(),
                if (_detail != null) _draftDetail(_detail!),
                if (!_creating && _detail == null && _drafts != null) ...[
                  if (_drafts!.items.isEmpty)
                    const AcademicText(
                      'Your private teaching drafts will appear here.',
                    ),
                  for (final draft in _drafts!.items)
                    ListTile(
                      leading: const Icon(Icons.description_outlined),
                      title: Text(l(teachingKindLabel(draft.kind.value))),
                      subtitle: Text(
                        '${l('Revision')} ${draft.revision} · ${l(teachingStateLabel(draft.state.value))}',
                      ),
                      onTap: !_busy ? () => _open(draft.id) : null,
                    ),
                  if (_drafts!.lastPage > 1)
                    Wrap(
                      spacing: 8,
                      children: [
                        Text(
                          '${l('Page')} ${_drafts!.page} / ${_drafts!.lastPage}',
                        ),
                        TextButton(
                          onPressed: !_busy && _drafts!.page > 1
                              ? () => _perform(
                                  (epoch) => _loadGroup(
                                    epoch,
                                    _group!,
                                    draftPage: _drafts!.page - 1,
                                  ),
                                )
                              : null,
                          child: const AcademicText('Previous'),
                        ),
                        TextButton(
                          onPressed: !_busy && _drafts!.page < _drafts!.lastPage
                              ? () => _perform(
                                  (epoch) => _loadGroup(
                                    epoch,
                                    _group!,
                                    draftPage: _drafts!.page + 1,
                                  ),
                                )
                              : null,
                          child: const AcademicText('Next'),
                        ),
                      ],
                    ),
                ],
              ],
            ],
          );
    return ScreenScaffold(
      title: l(widget.insightsOnly ? 'Test insights' : 'Teaching assistant'),
      body: body,
    );
  }
}
