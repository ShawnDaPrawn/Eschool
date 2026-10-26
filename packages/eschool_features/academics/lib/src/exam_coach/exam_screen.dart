import 'dart:async';
import 'dart:convert';
import 'dart:typed_data';

import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:uuid/uuid.dart';

import '../localization/academic_text.dart';
import '../tutor/tutor_working_crop.dart';
import '../wellbeing/private_report_image.dart';
import '../wellbeing/report_photo_picker.dart';
import 'exam_paper_editor.dart';
import 'exam_repository.dart';
import '../private/private_pending_intent.dart';
import 'exam_approved_crop.dart';
import '../tutor/tutor_repository.dart';

/// Private online reader/editor: no memo, working or photo enters a local cache.
class ExamScreen extends StatefulWidget {
  const ExamScreen({
    required this.repository,
    required this.staff,
    this.canRead,
    this.canWrite,
    this.selectPhoto,
    this.selectCameraPhoto,
    this.exportPdf,
    super.key,
  });
  final ExamRepository repository;
  final bool staff;
  final bool Function()? canRead, canWrite;
  final Future<Uint8List?> Function()? selectPhoto, selectCameraPhoto;
  final Future<void> Function(Uint8List, String)? exportPdf;
  @override
  State<ExamScreen> createState() => _ExamScreenState();
}

class _ExamScreenState extends State<ExamScreen> with WidgetsBindingObserver {
  final _scroll = ScrollController();
  final _working = TextEditingController(),
      _reason = TextEditingController(),
      _units = TextEditingController();
  ExamCoachConfiguration? _config;
  ExamCoachStaffChoices? _choices;
  ExamCoachPaperList? _papers;
  ExamCoachPaperVersion? _paper;
  ExamCoachPaperWrite? _editorSeed;
  ExamCoachExamDates? _dates;
  ExamCoachStudyPlan? _plan;
  ExamCoachPlanList? _plans;
  ExamCoachAnswerList? _answers;
  ExamCoachAnswerDetail? _answer;
  ExamWriteIntent? _write;
  PrivatePendingIntent? _sealed;
  String? _pendingKind;
  ExamPhotoIntent? _upload;
  ExamCoachPlanWrite? _planWrite;
  int? _planGroup;
  ExamCoachAnswerFeedbackWrite? _feedbackWrite;
  ({String kind, String operation, int group})? _recovery;
  Uint8List? _picked, _photo;
  String? _cropOperation, _question, _examId;
  int? _group;
  final _versions = <String>{};
  String _error = '';
  bool _busy = false,
      _background = false,
      _editor = false,
      _shared = false,
      _withdraw = false;
  int _epoch = 0, _minutes = 20;
  Timer? _expiry;
  bool get _pending =>
      _write != null ||
      _upload != null ||
      _planWrite != null ||
      _feedbackWrite != null ||
      _revokeOperation != null ||
      _recovery != null;
  bool get _read =>
      mounted &&
      !_background &&
      widget.repository.isActive &&
      (widget.canRead?.call() ?? true);
  bool get _writable =>
      _read && _config?.writable == true && (widget.canWrite?.call() ?? true);
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    unawaited(_load());
  }

  void _erase() {
    _expiry?.cancel();
    _expiry = null;
    _epoch++;
    _config = null;
    _choices = null;
    _papers = null;
    _paper = null;
    _editorSeed = null;
    _dates = null;
    _plan = null;
    _plans = null;
    _answers = null;
    _answer = null;
    _photo = null;
    _picked = null;
    _upload = null;
    _write = null;
    _feedbackWrite = null;
    _planWrite = null;
    _group = null;
    _versions.clear();
    _question = null;
    _examId = null;
    _editor = false;
    _shared = false;
    _withdraw = false;
    _working.clear();
    _reason.clear();
    _units.clear();
    _busy = false;
    _error = '';
    // Only an opaque, account-bound operation locator remains after erasure.
    // Exact submitted bodies remain sealed for at most fifteen minutes; they
    // are reopened only after a fresh configuration/source check.
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state != AppLifecycleState.resumed) {
      setState(() {
        _background = true;
        _erase();
      });
    } else if (_background) {
      _background = false;
      unawaited(_load());
    }
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    _expiry?.cancel();
    _epoch++;
    _sealed?.close();
    _scroll.dispose();
    _working.dispose();
    _reason.dispose();
    _units.dispose();
    super.dispose();
  }

  Future<void> _run(Future<void> Function() action) async {
    if (!_read || _busy) return;
    final epoch = _epoch;
    setState(() {
      _busy = true;
      _error = '';
    });
    try {
      await action();
    } catch (e) {
      if (!_read || epoch != _epoch) return;
      if (e is ApiException && [401, 403, 404, 409].contains(e.statusCode)) {
        final denied = [401, 403, 404].contains(e.statusCode);
        _clearPending();
        _erase();
        _error = denied
            ? 'Reopen Exam Coach to check your access.'
            : 'The paper or timetable changed. Reopen Exam Coach.';
      } else {
        if (e is ApiException && e.statusCode == 422) {
          if (_write != null && _write!.body.containsKey('questions')) {
            _editorSeed = ExamCoachPaperWrite.fromJson(_write!.body);
            _editor = true;
          }
          _write = null;
          _upload = null;
          _feedbackWrite = null;
          _planWrite = null;
          _clearPending();
        }
        _error = e is ApiException && e.isApiMessage
            ? e.errorMessage
            : 'The result is not confirmed. Retry the same operation or check its status.';
      }
      if (mounted) {
        WidgetsBinding.instance.addPostFrameCallback((_) {
          if (mounted && _scroll.hasClients && _error.isNotEmpty)
            _scroll.jumpTo(0);
        });
      }
    } finally {
      if (_read && epoch == _epoch) {
        setState(() => _busy = false);
      } else if (mounted)
        setState(() {});
    }
  }

  bool _current(int epoch) => _read && epoch == _epoch;
  Future<void> _load() async {
    if (!_read) return;
    await _run(() async {
      final epoch = _epoch;
      final value = await widget.repository.configuration();
      if (!_current(epoch)) return;
      _config = value;
      final groups = widget.staff ? value.staffGroups : value.learnerGroups;
      if (groups.isNotEmpty) {
        _group =
            _recovery != null && groups.any((g) => g.id == _recovery!.group)
            ? _recovery!.group
            : groups.first.id;
        await _loadGroup(_group!, epoch);
      }
      if (!widget.staff) {
        final plans = await widget.repository.plans();
        if (_current(epoch)) _plans = plans;
      }
      if (_recovery != null && _current(epoch)) await _recover(epoch);
    });
  }

  Future<void> _loadGroup(int group, int epoch, {int page = 1}) async {
    final papers = await widget.repository.papers(
      group,
      staff: widget.staff,
      page: page,
    );
    if (!_current(epoch)) return;
    _papers = papers;
    if (widget.staff) {
      final choices = await widget.repository.choices(group);
      if (!_current(epoch)) return;
      _choices = choices;
    } else {
      final dates = await widget.repository.dates(group);
      if (!_current(epoch)) return;
      _dates = dates;
    }
    final answers = await widget.repository.answers(
      group: widget.staff ? group : null,
    );
    if (_current(epoch)) _answers = answers;
  }

  Future<void> _switch(int group) => _run(() async {
    final epoch = _epoch;
    _group = group;
    _paper = null;
    _answer = null;
    _photo = null;
    _picked = null;
    _choices = null;
    _dates = null;
    _papers = null;
    _versions.clear();
    _editor = false;
    _working.clear();
    _reason.clear();
    await _loadGroup(group, epoch);
  });
  Future<void> _open(ExamCoachPaperSummary item) => _run(() async {
    final epoch = _epoch;
    final value = await widget.repository.paper(
      _group!,
      item.versionId,
      staff: widget.staff,
    );
    if (_current(epoch)) {
      _paper = value;
      _question = value.questions.first.number;
      _editor = false;
    }
  });
  void _stage(Map<String, dynamic> body) {
    _sealed?.close();
    _pendingKind = body['kind'] as String;
    _sealed = PrivatePendingIntent(
      school: widget.repository.schoolId,
      reader: widget.repository.readerId,
      body: body,
    );
  }

  void _clearPending() {
    _sealed?.close();
    _sealed = null;
    _pendingKind = null;
    _recovery = null;
  }

  void _stageWrite() {
    _stage({'kind': 'write', 'path': _write!.path, 'body': _write!.body});
  }

  Future<void> _restorePending(int epoch) async {
    final locator = _recovery;
    if (locator == null || !_current(epoch) || _sealed == null) return;
    if (_sealed!.expired) {
      _sealed!.close();
      _sealed = null;
      _error =
          'The exact pending request expired. Keep checking its operation status before starting another.';
      return;
    }
    final groups = widget.staff ? _config!.staffGroups : _config!.learnerGroups;
    if (!groups.any((g) => g.id == locator.group))
      throw ApiException(
        'Reopen Exam Coach to check your access.',
        statusCode: 403,
        isApiMessage: true,
      );
    final saved = _sealed!.open(
      school: widget.repository.schoolId,
      reader: widget.repository.readerId,
      authorized: _read,
    );
    _group = locator.group;
    switch (saved['kind']) {
      case 'write':
        if (locator.kind == 'review')
          await _readAnswer(saved['answer'] as String, epoch);
        if (_current(epoch))
          _write = ExamWriteIntent(
            saved['path'] as String,
            Map<String, dynamic>.from(saved['body'] as Map),
          );
      case 'photo':
        final crop = saved['crop'] as Map;
        _upload = ExamPhotoIntent(
          locator.group,
          saved['version'] as String,
          saved['question'] as String,
          TutorPhotoIntent(
            session: saved['version'] as String,
            operation: locator.operation,
            bytes: base64Decode(crop['bytes'] as String),
            x: 0,
            y: 0,
            width: crop['width'] as int,
            height: crop['height'] as int,
            approved: true,
          ),
        );
      case 'feedback':
        await _readAnswer(saved['answer'] as String, epoch);
        if (_current(epoch))
          _feedbackWrite = ExamCoachAnswerFeedbackWrite.fromJson(
            Map<String, dynamic>.from(saved['body'] as Map),
          );
      case 'plan':
        _planGroup = locator.group;
        _planWrite = ExamCoachPlanWrite.fromJson(
          Map<String, dynamic>.from(saved['body'] as Map),
        );
    }
  }

  Future<void> _submitPaper(ExamCoachPaperWrite input) async {
    if (_busy || _pending || !_writable || _group == null) return;
    final suffix = _paper == null ? '' : '/${_paper!.id}/versions';
    _write = ExamWriteIntent(
      'staff/groups/$_group/papers$suffix',
      input.toJson(),
    );
    _recovery = (kind: 'paper', operation: input.operationId, group: _group!);
    _stageWrite();
    _editor = false;
    await _retryWrite();
  }

  Future<void> _retryWrite() => _run(() async {
    if (!_writable || _write == null) return;
    final epoch = _epoch;
    final intent = _write!;
    if (intent.path.endsWith('/review')) {
      await widget.repository.review(_group!, _answer!.id, intent);
      if (_current(epoch)) {
        _write = null;
        _clearPending();
        await _readAnswer(_answer!.id, epoch);
      }
    } else {
      final receipt = await widget.repository.writePaper(intent);
      if (!_current(epoch)) return;
      _write = null;
      _clearPending();
      _paper = null;
      await _loadGroup(_group!, epoch);
      if (receipt.versionId != null) {
        final paper = await widget.repository.paper(
          _group!,
          receipt.versionId!,
          staff: true,
        );
        if (_current(epoch)) _paper = paper;
      }
    }
  });
  Future<void> _transition(bool withdraw) async {
    if (_busy || _pending || !_writable || _paper == null || _group == null)
      return;
    final p = _paper!;
    if (withdraw && _reason.text.trim().isEmpty) {
      setState(() => _error = 'Give a reason for withdrawing this paper.');
      return;
    }
    final op = const Uuid().v4();
    _write = ExamWriteIntent(
      'staff/groups/$_group/papers/${p.id}/${withdraw ? 'withdraw' : 'versions/${p.versionId}/publish'}',
      {
        'operation_id': op,
        'expected_revision': p.revision!,
        if (withdraw) 'reason': _reason.text.trim(),
      },
    );
    _recovery = (kind: 'paper', operation: op, group: _group!);
    _stageWrite();
    await _retryWrite();
  }

  Future<void> _recover(int epoch) async {
    final locator = _recovery;
    if (locator == null) return;
    final groups = widget.staff ? _config!.staffGroups : _config!.learnerGroups;
    if (!groups.any((g) => g.id == locator.group))
      throw ApiException(
        'Reopen Exam Coach to check your access.',
        statusCode: 403,
        isApiMessage: true,
      );
    if (locator.kind == 'plan') {
      await _restorePending(epoch);
      return;
    }
    try {
      if (locator.kind == 'revoke' && _revokeAnswer != null) {
        await widget.repository.revoke(_revokeAnswer!, locator.operation);
        if (_current(epoch)) {
          _revokeAnswer = null;
          _revokeOperation = null;
          _recovery = null;
          _answer = null;
          _photo = null;
        }
      } else if (locator.kind == 'photo') {
        final value = await widget.repository.answerOperation(
          locator.operation,
        );
        if (_current(epoch)) {
          _upload = null;
          if (value.state.value != 'prepared' ||
              _sealed == null ||
              _pendingKind != 'feedback') {
            _feedbackWrite = null;
            _clearPending();
          } else {
            await _restorePending(epoch);
          }
          await _readAnswer(value.id, epoch);
        }
      } else {
        final value = await widget.repository.operation(
          locator.group,
          locator.operation,
        );
        if (!_current(epoch)) return;
        _clearPending();
        _write = null;
        _group = locator.group;
        await _loadGroup(locator.group, epoch);
        if (value.review != null) {
          await _readAnswer(value.review!.answerId, epoch);
        }
        if (value.paper?.versionId != null) {
          final p = await widget.repository.paper(
            locator.group,
            value.paper!.versionId!,
            staff: true,
          );
          if (_current(epoch)) _paper = p;
        }
      }
    } on ApiException catch (e) {
      if (e.statusCode != 404) rethrow;
      if (_current(epoch)) {
        _error =
            'The operation is not recorded yet. Check again or retry its exact saved request; a new UUID will not be created.';
        await _restorePending(epoch);
      }
    }
  }

  Future<void> _makePlan() => _run(() async {
    if (!_writable ||
        (_planWrite == null && (_examId == null || _versions.isEmpty))) {
      return;
    }
    final epoch = _epoch;
    if (_planWrite == null) {
      final exam = _dates!.exams.firstWhere((e) => e.id.toString() == _examId);
      _planGroup = _group;
      _planWrite = ExamCoachPlanWrite(
        operationId: const Uuid().v4(),
        examTimetableId: exam.id,
        scheduleSignature: exam.scheduleSignature,
        paperVersionIds: _versions.toList(),
        studyMinutes: _minutes,
      );
    }
    _recovery ??= (
      kind: 'plan',
      operation: _planWrite!.operationId,
      group: _planGroup!,
    );
    if (_sealed == null) _stage({'kind': 'plan', 'body': _planWrite!.toJson()});
    final receipt = await widget.repository.createPlan(
      _planGroup!,
      _planWrite!,
    );
    if (!_current(epoch)) return;
    _planWrite = null;
    _planGroup = null;
    _clearPending();
    final plan = await widget.repository.plan(receipt.id);
    if (!_current(epoch)) return;
    _plan = plan;
    final plans = await widget.repository.plans();
    if (_current(epoch)) _plans = plans;
  });
  Future<void> _readPlan(String id) => _run(() async {
    final epoch = _epoch;
    final value = await widget.repository.plan(id);
    if (_current(epoch)) _plan = value;
  });
  Future<void> _readAnswer(String id, int epoch) async {
    final value = await widget.repository.answer(
      id,
      group: widget.staff ? _group : null,
    );
    if (!_current(epoch)) return;
    _answer = value;
    _photo = null;
    _working.text = value.working ?? '';
    _reason.clear();
    _units.text = examMarkText(
      value.review?.markUnits ?? value.response?.markUnits ?? 0,
    );
    _expiry?.cancel();
    if (value.expiresAt != null) {
      final expiry = value.expiresAt!.toUtc();
      if (!expiry.isAfter(DateTime.now().toUtc())) {
        _answer = null;
        return;
      }
      _expiry = Timer(expiry.difference(DateTime.now().toUtc()), () {
        if (mounted) {
          setState(() {
            _answer = null;
            _photo = null;
            _working.clear();
            _reason.clear();
            _error = 'This private working has expired.';
          });
        }
      });
    }
    if (value.state.value != 'quarantined' && value.state.value != 'purged') {
      final bytes = await widget.repository.photo(
        id,
        group: widget.staff ? _group : null,
      );
      if (_current(epoch) && _answer?.id == id) _photo = bytes;
    }
  }

  Future<void> _pick(Future<Uint8List?> Function() picker) => _run(() async {
    if (!_writable || _config?.photoFeedbackEnabled != true || _paper == null) {
      return;
    }
    final epoch = _epoch;
    final bytes = await picker();
    if (!_current(epoch)) return;
    if (bytes == null) return;
    if (bytes.length > 4194304) {
      _error = 'Choose a JPEG or PNG photo up to 4 MB.';
      return;
    }
    _picked = bytes;
    _cropOperation = const Uuid().v4();
    _shared = false;
  });
  Future<void> _uploadPhoto(ExamPhotoIntent intent) async {
    if (_busy || _pending || !_writable) return;
    await _run(() async {
      final epoch = _epoch;
      final crop = await examApprovedCrop(intent.crop);
      if (!_current(epoch) || !_shared) return;
      _upload = ExamPhotoIntent(
        intent.group,
        intent.version,
        intent.question,
        crop,
      );
      _stage({
        'kind': 'photo',
        'version': intent.version,
        'question': intent.question,
        'crop': {
          'bytes': base64Encode(crop.bytes),
          'width': crop.width,
          'height': crop.height,
        },
      });
      _recovery = (
        kind: 'photo',
        operation: crop.operation,
        group: intent.group,
      );
      _picked = null;
      final receipt = await widget.repository.upload(_upload!);
      if (!_current(epoch)) return;
      _upload = null;
      _clearPending();
      await _readAnswer(receipt.id, epoch);
    });
  }

  Future<void> _retryPhoto() => _run(() async {
    if (!_writable || _upload == null) return;
    final epoch = _epoch;
    final value = await widget.repository.upload(_upload!);
    if (!_current(epoch)) return;
    _upload = null;
    _clearPending();
    await _readAnswer(value.id, epoch);
  });
  Future<void> _feedback() => _run(() async {
    if (!_writable || _answer == null) return;
    final epoch = _epoch;
    _recovery = (
      kind: 'photo',
      operation: _answer!.operationId,
      group: _group!,
    );
    _feedbackWrite ??= ExamCoachAnswerFeedbackWrite(
      operationId: _answer!.operationId,
      working: _working.text.trim(),
    );
    if (_sealed == null)
      _stage({
        'kind': 'feedback',
        'answer': _answer!.id,
        'body': _feedbackWrite!.toJson(),
      });
    final value = await widget.repository.feedback(
      _answer!.id,
      _feedbackWrite!,
    );
    if (!_current(epoch)) return;
    _feedbackWrite = null;
    _clearPending();
    _answer = value;
    _photo = null;
    if (value.state.value == 'completed') {
      await _readAnswer(value.id, epoch);
    } else if (value.state.value == 'quarantined') {
      _working.clear();
      _reason.clear();
    }
  });
  Future<void> _review() async {
    if (_busy || _pending) return;
    if (!_writable ||
        _answer?.revision == null ||
        _reason.text.trim().isEmpty ||
        examMarkUnits(_units.text) == null) {
      setState(() => _error = 'Enter verified marks and a review reason.');
      return;
    }
    final op = const Uuid().v4();
    _write =
        ExamWriteIntent('staff/groups/$_group/answers/${_answer!.id}/review', {
          'operation_id': op,
          'expected_revision': _answer!.revision,
          'mark_units': examMarkUnits(_units.text)!,
          'reason': _reason.text.trim(),
        });
    _recovery = (kind: 'review', operation: op, group: _group!);
    _stage({
      'kind': 'write',
      'path': _write!.path,
      'body': _write!.body,
      'answer': _answer!.id,
    });
    await _retryWrite();
  }

  String? _revokeOperation, _revokeAnswer;
  Future<void> _revoke() => _run(() async {
    if (!_writable || _answer == null) return;
    final epoch = _epoch;
    _revokeOperation ??= const Uuid().v4();
    _revokeAnswer ??= _answer!.id;
    _recovery = (kind: 'revoke', operation: _revokeOperation!, group: _group!);
    await widget.repository.revoke(_revokeAnswer!, _revokeOperation!);
    if (!_current(epoch)) return;
    _revokeOperation = null;
    _revokeAnswer = null;
    _recovery = null;
    _answer = null;
    _photo = null;
    _working.clear();
    _feedbackWrite = null;
    final rows = await widget.repository.answers();
    if (_current(epoch)) _answers = rows;
  });
  Future<void> _pdf(bool memo) => _run(() async {
    final epoch = _epoch;
    final value = await widget.repository.pdf(
      _group!,
      _paper!.versionId,
      staff: widget.staff,
      memo: memo,
    );
    if (_current(epoch)) {
      await widget.exportPdf?.call(
        value,
        memo ? 'exam-memo.pdf' : 'exam-paper.pdf',
      );
    }
  });
  Widget _button(String label, VoidCallback? action) => Padding(
    padding: const EdgeInsets.symmetric(vertical: 4),
    child: OutlinedButton(
      onPressed: _busy ? null : action,
      child: AcademicText(label),
    ),
  );
  @override
  Widget build(BuildContext context) {
    final c = _config;
    final groups = widget.staff ? c?.staffGroups : c?.learnerGroups;
    return ScreenScaffold(
      title: academicText(context, 'Exam Coach'),
      body: _background
          ? const Center(
              child: AcademicText('Reopen Exam Coach to check your access.'),
            )
          : ListView(
              controller: _scroll,
              padding: const EdgeInsets.all(16),
              children: [
                if (_busy) const LinearProgressIndicator(),
                if (_error.isNotEmpty)
                  Semantics(liveRegion: true, child: AcademicText(_error)),
                if (c == null && !_busy)
                  _button('Reopen Exam Coach', () => unawaited(_load())),
                if (c != null) ...[
                  if (groups!.isEmpty)
                    const AcademicText(
                      'No current subject group is available.',
                    ),
                  if (groups.isNotEmpty)
                    DropdownButtonFormField<int>(
                      key: ValueKey('exam-group-$_group'),
                      initialValue: _group,
                      isExpanded: true,
                      decoration: InputDecoration(
                        labelText: academicText(context, 'Subject group'),
                      ),
                      items: groups
                          .map(
                            (g) => DropdownMenuItem(
                              value: g.id,
                              child: Text(
                                '${g.subjectName} · ${g.name}',
                                overflow: TextOverflow.ellipsis,
                              ),
                            ),
                          )
                          .toList(),
                      onChanged: _busy || _pending
                          ? null
                          : (v) {
                              if (v != null) unawaited(_switch(v));
                            },
                    ),
                  if (!_writable) const AcademicText('Read-only access'),
                  if (_planWrite != null)
                    _button(
                      'Retry exact study plan',
                      () => unawaited(_makePlan()),
                    ),
                  if (_recovery != null)
                    _button(
                      'Check operation status',
                      () => unawaited(_run(() => _recover(_epoch))),
                    ),
                  if (_write != null)
                    _button(
                      'Retry exact saved request',
                      () => unawaited(_retryWrite()),
                    ),
                  if (_upload != null)
                    _button(
                      'Retry exact approved upload',
                      () => unawaited(_retryPhoto()),
                    ),
                  if (_editor && _choices != null)
                    ExamPaperEditor(
                      choices: _choices!,
                      group: _group!,
                      paper: _paper,
                      initialWrite: _editorSeed,
                      onSave: (v) => unawaited(_submitPaper(v)),
                      onCancel: () => setState(() {
                        _editor = false;
                        _editorSeed = null;
                      }),
                    )
                  else ...[
                    if (widget.staff &&
                        _writable &&
                        _write == null &&
                        _recovery == null &&
                        _choices?.topics.isNotEmpty == true)
                      _button(
                        'Prepare licensed paper',
                        () => setState(() {
                          _paper = null;
                          _editorSeed = null;
                          _editor = true;
                        }),
                      ),
                    const AcademicText('Past papers'),
                    for (final p
                        in _papers?.papers ?? <ExamCoachPaperSummary>[])
                      Card(
                        child: ListTile(
                          title: Text(p.title),
                          subtitle: Text(
                            '${p.paperYear} · ${p.paperCode} · ${p.state.value}',
                          ),
                          onTap: _busy || _pending
                              ? null
                              : () => unawaited(_open(p)),
                          trailing:
                              !widget.staff && p.state.value == 'published'
                              ? Checkbox(
                                  value: _versions.contains(p.versionId),
                                  onChanged: _busy || _pending
                                      ? null
                                      : (v) => setState(
                                          () => v == true
                                              ? _versions.add(p.versionId)
                                              : _versions.remove(p.versionId),
                                        ),
                                )
                              : null,
                        ),
                      ),
                    if (_papers?.hasMore == true)
                      _button(
                        'Next papers',
                        () => unawaited(
                          _run(
                            () => _loadGroup(
                              _group!,
                              _epoch,
                              page: _papers!.page + 1,
                            ),
                          ),
                        ),
                      ),
                    if (_papers != null && _papers!.page > 1)
                      _button(
                        'Previous papers',
                        () => unawaited(
                          _run(
                            () => _loadGroup(
                              _group!,
                              _epoch,
                              page: _papers!.page - 1,
                            ),
                          ),
                        ),
                      ),
                    if (_paper != null) ..._paperWidgets(),
                    if (!widget.staff) ..._planWidgets(),
                    const Divider(),
                    const AcademicText('Submitted working'),
                    for (final a
                        in _answers?.answers ?? <ExamCoachAnswerSummary>[])
                      ListTile(
                        title: Text(
                          '${a.learnerName ?? academicText(context, 'Question')} ${a.questionNumber}',
                        ),
                        subtitle: Text(a.state.value),
                        onTap: _busy || _pending || _upload != null
                            ? null
                            : () => unawaited(
                                _run(() => _readAnswer(a.id, _epoch)),
                              ),
                      ),
                    if (_answers?.hasMore == true)
                      _button(
                        'Next working',
                        () => unawaited(
                          _run(() async {
                            final epoch = _epoch;
                            final value = await widget.repository.answers(
                              group: widget.staff ? _group : null,
                              page: _answers!.page + 1,
                            );
                            if (_current(epoch)) _answers = value;
                          }),
                        ),
                      ),
                    if (_answer != null) ..._answerWidgets(),
                  ],
                ],
              ],
            ),
    );
  }

  List<Widget> _paperWidgets() {
    final p = _paper!;
    return [
      const Divider(),
      Text(p.title, style: Theme.of(context).textTheme.titleLarge),
      Text('${p.framework} · ${p.paperCode} · ${p.paperYear}'),
      Text(
        '${academicText(context, 'Permission reference')}: ${p.licenceReference}',
      ),
      for (final q in p.questions)
        ListTile(
          title: Text('${q.number}. ${q.prompt}'),
          subtitle: Text(
            '${examMarkText(q.maxUnits)} ${academicText(context, 'marks')}${widget.staff && q.memo != null ? '\n${q.memo}' : ''}',
          ),
        ),
      if (widget.exportPdf != null)
        _button('Download question paper', () => unawaited(_pdf(false))),
      if (widget.staff && widget.exportPdf != null)
        _button('Download staff memo', () => unawaited(_pdf(true))),
      if (widget.staff &&
          p.canEdit == true &&
          _writable &&
          _write == null &&
          _recovery == null) ...[
        _button('Prepare revised draft', () => setState(() => _editor = true)),
        if (p.state.value == 'draft')
          _button(
            'Publish reviewed paper',
            () => unawaited(_transition(false)),
          ),
        if (p.state.value == 'published')
          _button('Withdraw paper', () => setState(() => _withdraw = true)),
        if (_withdraw) ...[
          TextField(
            controller: _reason,
            maxLength: 2000,
            decoration: InputDecoration(
              labelText: academicText(context, 'Withdrawal reason'),
            ),
          ),
          _button('Confirm withdrawal', () => unawaited(_transition(true))),
        ],
      ],
      if (!widget.staff && p.state.value == 'published') ...[
        if (_config?.photoFeedbackEnabled != true)
          const AcademicText(
            'Photo feedback is currently unavailable. Your papers and study plan are still available.',
          ),
        if (_config?.photoFeedbackEnabled == true &&
            _writable &&
            _upload == null &&
            _recovery == null) ...[
          DropdownButtonFormField<String>(
            key: ValueKey(p.versionId),
            initialValue: _question,
            isExpanded: true,
            decoration: InputDecoration(
              labelText: academicText(context, 'Question'),
            ),
            items: p.questions
                .map(
                  (q) =>
                      DropdownMenuItem(value: q.number, child: Text(q.number)),
                )
                .toList(),
            onChanged: _busy ? null : (v) => setState(() => _question = v),
          ),
          _button(
            'Choose working photo',
            () => unawaited(_pick(widget.selectPhoto ?? selectReportPhoto)),
          ),
          if (widget.selectCameraPhoto != null)
            _button(
              'Photograph working',
              () => unawaited(_pick(widget.selectCameraPhoto!)),
            ),
          if (_picked != null) ...[
            CheckboxListTile(
              value: _shared,
              onChanged: _busy
                  ? null
                  : (v) => setState(() => _shared = v ?? false),
              title: const AcademicText(
                'Share this cropped working and provisional feedback with my allocated teacher.',
              ),
            ),
            TutorWorkingCrop(
              key: ValueKey(_cropOperation),
              bytes: _picked!,
              session: p.versionId,
              operation: _cropOperation!,
              onCancel: () => setState(() => _picked = null),
              onApproved: (crop) {
                if (!_shared) {
                  setState(
                    () =>
                        _error = 'Confirm sharing with your allocated teacher.',
                  );
                  return;
                }
                unawaited(
                  _uploadPhoto(
                    ExamPhotoIntent(_group!, p.versionId, _question!, crop),
                  ),
                );
              },
            ),
          ],
        ],
      ],
    ];
  }

  List<Widget> _planWidgets() => [
    const Divider(),
    const AcademicText('Study plan'),
    if (_dates?.exams.isEmpty == true)
      const AcademicText(
        'No published upcoming exam is available for this subject.',
      ),
    if (_dates?.exams.isNotEmpty == true) ...[
      DropdownButtonFormField<String>(
        key: ValueKey('exam-date-$_group'),
        initialValue: _examId,
        isExpanded: true,
        decoration: InputDecoration(
          labelText: academicText(context, 'Upcoming exam'),
        ),
        items: _dates!.exams
            .map(
              (e) => DropdownMenuItem(
                value: e.id.toString(),
                child: Text('${e.name} · ${e.examOn}'),
              ),
            )
            .toList(),
        onChanged: _busy || _pending
            ? null
            : (v) => setState(() => _examId = v),
      ),
      DropdownButtonFormField<int>(
        initialValue: _minutes,
        decoration: InputDecoration(
          labelText: academicText(context, 'Minutes per study day'),
        ),
        items: const [
          10,
          20,
          30,
          40,
          50,
          60,
        ].map((v) => DropdownMenuItem(value: v, child: Text('$v'))).toList(),
        onChanged: _busy || _pending
            ? null
            : (v) => setState(() => _minutes = v!),
      ),
      const AcademicText(
        'Select up to five published papers to weight your study plan.',
      ),
      _button(
        _planWrite == null ? 'Create study plan' : 'Retry exact study plan',
        _writable &&
                _examId != null &&
                _versions.isNotEmpty &&
                _versions.length <= 5
            ? () => unawaited(_makePlan())
            : null,
      ),
    ],
    for (final plan in _plans?.plans ?? <ExamCoachPlanSummary>[])
      ListTile(
        title: Text(plan.examOn),
        subtitle: Text(plan.papers.map((p) => p.title).join(', ')),
        onTap: _busy ? null : () => unawaited(_readPlan(plan.id)),
      ),
    if (_plans?.hasMore == true)
      _button(
        'Older study plans',
        () => unawaited(
          _run(() async {
            final epoch = _epoch;
            final value = await widget.repository.plans(page: _plans!.page + 1);
            if (_current(epoch)) _plans = value;
          }),
        ),
      ),
    if (_plan != null) ...[
      Text('${academicText(context, 'Exam date')}: ${_plan!.examOn}'),
      for (final day in _plan!.days)
        ListTile(
          title: Text(day.topicName),
          subtitle: Text(
            '${day.date} · ${day.minutes} ${academicText(context, 'minutes')}',
          ),
        ),
    ],
  ];
  String _failure(String? code) => switch (code) {
    'school_inference_paused' =>
      'Photo feedback is paused by your school. Ask your teacher for help.',
    'budget_currency_changed' =>
      'The school budget settings changed. Ask your school administrator to check them.',
    'budget_exceeded' ||
    'school_budget_exceeded' ||
    'learner_budget_exceeded' =>
      'The current feedback allowance is used up. Ask your teacher for help.',
    _ =>
      'Photo feedback is unavailable for this submission. Your teacher can help; your paper and study plan remain available.',
  };
  List<Widget> _answerWidgets() {
    final a = _answer!;
    return [
      const Divider(),
      const AcademicText('Private working feedback'),
      if (a.state.value == 'quarantined')
        const AcademicText(
          'This working was withheld for safeguarding. Speak to a trusted adult.',
        )
      else if (a.state.value == 'purged')
        const AcademicText('This private working has expired.')
      else ...[
        if (_photo != null)
          PrivateReportImage(
            key: ValueKey('exam-photo-${a.id}'),
            base64: base64Encode(_photo!),
            label: academicText(context, 'Approved working crop'),
            maximumEdge: 768,
            maximumEncodedLength: 1398104,
          ),
        if (a.state.value == 'failed') AcademicText(_failure(a.failureCode)),
        if (a.state.value == 'pending')
          _button(
            'Check feedback status',
            () => unawaited(_run(() => _readAnswer(a.id, _epoch))),
          ),
        if (a.working?.isNotEmpty == true) Text(a.working!),
        if (a.response != null) ...[
          const AcademicText(
            'Provisional feedback — your teacher must verify this.',
          ),
          Text(
            '${examMarkText(a.response!.markUnits)} ${academicText(context, 'suggested marks')} · ${(a.response!.confidenceBasisPoints / 100).toStringAsFixed(1)}% ${academicText(context, 'model confidence')}',
          ),
          Text(a.response!.feedback),
        ],
        if (a.review != null) ...[
          const AcademicText('Teacher review'),
          Text(
            '${examMarkText(a.review!.markUnits)} ${academicText(context, 'marks')}',
          ),
          Text(a.review!.reason),
        ],
        if (widget.staff &&
            a.response != null &&
            _writable &&
            _write == null &&
            _recovery == null) ...[
          TextField(
            controller: _units,
            keyboardType: TextInputType.number,
            decoration: InputDecoration(
              labelText: academicText(context, 'Verified marks'),
            ),
          ),
          TextField(
            controller: _reason,
            maxLength: 2000,
            minLines: 2,
            maxLines: 4,
            decoration: InputDecoration(
              labelText: academicText(context, 'Review reason'),
            ),
          ),
          _button(
            'Confirm or correct provisional feedback',
            () => unawaited(_review()),
          ),
        ],
        if (!widget.staff && _writable) ...[
          if (a.state.value == 'prepared') ...[
            TextField(
              controller: _working,
              maxLength: 4000,
              minLines: 2,
              maxLines: 5,
              readOnly: _feedbackWrite != null,
              decoration: InputDecoration(
                labelText: academicText(
                  context,
                  'Working explanation (optional)',
                ),
              ),
            ),
            _button(
              _feedbackWrite == null
                  ? 'Request provisional feedback'
                  : 'Retry exact feedback request',
              () => unawaited(_feedback()),
            ),
          ],
          _button('Remove my working photo', () => unawaited(_revoke())),
        ],
      ],
    ];
  }
}
