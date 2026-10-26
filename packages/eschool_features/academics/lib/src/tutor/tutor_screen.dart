import 'dart:async';
import 'dart:convert';
import 'dart:typed_data';

import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:uuid/uuid.dart';

import '../localization/academic_text.dart';
import '../wellbeing/report_photo_picker.dart';
import '../wellbeing/private_report_image.dart';
import 'tutor_repository.dart';
import 'tutor_working_crop.dart';

String tutorFailureText(String? reason) => switch (reason) {
  'actor_daily_cap' || 'learner_daily_cap' || 'tutor_daily_turn_cap' =>
    'Your tutor allowance is used for today. You can still use your school learning material.',
  'school_daily_cap' ||
  'school_inference_paused' ||
  'budget_currency_changed' =>
    'The school tutor allowance needs an administrator. You can still use your learning material.',
  'inference_disabled' ||
  'provider_not_qualified' ||
  'budget_not_configured' ||
  'adapter_unavailable' ||
  'inference_key_missing' =>
    'Your school is preparing the tutor. Try again when it is available.',
  _ => 'A reply was not confirmed. Check this turn before sending another.',
};

/// Online private conversation. Only opaque locators survive backgrounding;
/// the server reauthorizes and recovers an owner's pending human draft.
class TutorScreen extends StatefulWidget {
  const TutorScreen({
    required this.repository,
    this.selectPhoto,
    this.selectCameraPhoto,
    this.initialSession,
    this.canWrite,
    super.key,
  });
  final TutorRepository repository;
  final Future<Uint8List?> Function()? selectPhoto, selectCameraPhoto;
  final String? initialSession;
  final bool Function()? canWrite;
  @override
  State<TutorScreen> createState() => _TutorScreenState();
}

class _TutorScreenState extends State<TutorScreen> with WidgetsBindingObserver {
  final _question = TextEditingController(), _working = TextEditingController();
  TutorConfiguration? _configuration;
  TutorSessionList? _sessions;
  TutorSessionDetail? _detail;
  TutorTurnIntent? _intent;
  TutorPhotoIntent? _uploadIntent;
  TutorPhotoReceipt? _workingPhoto;
  TutorSessionWrite? _creation;
  bool _pendingUpload = false;
  String? _closing;
  Uint8List? _picked;
  String? _sessionId, _operation, _error;
  int? _group;
  int _epoch = 0;
  bool _loading = true,
      _busy = false,
      _suspended = false,
      _picking = false,
      _unknown = false;
  bool get _active => mounted && !_suspended && widget.repository.isActive;
  bool _current(int epoch) => _active && epoch == _epoch;
  bool get _editable =>
      _active &&
      (widget.canWrite?.call() ?? true) &&
      !_busy &&
      !_loading &&
      !_unknown &&
      _picked == null;
  String l(String value) => academicText(context, value);

  @override
  void initState() {
    super.initState();
    _sessionId = widget.initialSession;
    WidgetsBinding.instance.addObserver(this);
    unawaited(_load());
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    _epoch++;
    _question.dispose();
    _working.dispose();
    _picked = null;
    _intent = null;
    _uploadIntent = null;
    super.dispose();
  }

  void _erase() {
    _question.clear();
    _working.clear();
    _configuration = null;
    _sessions = null;
    _detail = null;
    _picked = null;
    _intent = null;
    _uploadIntent = null;
    _workingPhoto = null;
    _busy = false;
    _error = null;
  }

  void _clearTurnDraft() {
    _question.clear();
    _working.clear();
    _intent = null;
    _operation = null;
    _uploadIntent = null;
    _workingPhoto = null;
    _pendingUpload = false;
  }

  @override
  void didUpdateWidget(TutorScreen oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.repository != widget.repository ||
        oldWidget.initialSession != widget.initialSession) {
      _epoch++;
      _erase();
      _sessionId = widget.initialSession;
      _operation = null;
      _creation = null;
      _closing = null;
      _unknown = false;
      _pendingUpload = false;
      unawaited(_load());
    }
  }

  Future<TutorSessionDetail> _latest(String id) async {
    final first = await widget.repository.read(id);
    return first.lastPage > 1
        ? widget.repository.read(id, page: first.lastPage)
        : first;
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state != AppLifecycleState.resumed) {
      _epoch++;
      _suspended = true;
      if (mounted) setState(_erase);
    } else {
      _suspended = false;
      if (!_picking) unawaited(_load());
    }
  }

  void _failed(Object error) {
    if (!mounted) return;
    if (error is ApiException && [401, 403, 404].contains(error.statusCode)) {
      _epoch++;
      _erase();
      setState(() {
        _loading = false;
        _error = 'Your tutor access changed. Reopen it after refreshing.';
      });
    } else {
      setState(
        () => _error = error is ApiException && error.isApiMessage
            ? error.errorMessage
            : 'The tutor could not be reached. Check the current conversation.',
      );
    }
  }

  Future<void> _load() async {
    final epoch = ++_epoch;
    if (!_active) return;
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final configuration = await widget.repository.configuration();
      if (!_current(epoch)) return;
      final sessions = await widget.repository.sessions();
      if (!_current(epoch)) return;
      TutorSessionDetail? detail;
      if (_sessionId != null) {
        detail = await _latest(_sessionId!);
        if (!_current(epoch)) return;
      }
      setState(() {
        _configuration = configuration;
        _sessions = sessions;
        _detail = detail;
        _group ??= configuration.groups.firstOrNull?.id;
        _loading = false;
        _busy = false;
      });
      _recover(detail);
    } catch (error) {
      if (_current(epoch)) {
        setState(() => _loading = false);
        _failed(error);
      }
    }
  }

  void _recover(TutorSessionDetail? detail) {
    if (!_active || detail == null) return;
    final pending = detail.turns
        .where((turn) => turn.state.value == 'pending')
        .firstOrNull;
    if (pending != null && pending.question != null) {
      final photos = detail.pendingPhotos
          .where((photo) => photo.operationId == pending.operationId)
          .toList();
      setState(() {
        _operation = pending.operationId;
        _intent = TutorTurnIntent(
          detail.id,
          TutorTurnWrite(
            operationId: pending.operationId,
            question: pending.question!,
            working: pending.working ?? '',
            photoIds: photos.map((photo) => photo.id).toSet(),
          ),
        );
        _unknown = true;
        _pendingUpload = false;
      });
    } else if (_operation != null) {
      final terminal = detail.turns
          .where(
            (turn) =>
                turn.operationId == _operation && turn.state.value != 'pending',
          )
          .firstOrNull;
      if (terminal != null) {
        setState(() {
          _unknown = false;
          _clearTurnDraft();
        });
        return;
      }
      final photo = detail.pendingPhotos
          .where((photo) => photo.operationId == _operation)
          .firstOrNull;
      if (photo != null && _pendingUpload) {
        setState(() {
          _workingPhoto = photo;
          _unknown = false;
          _pendingUpload = false;
          _uploadIntent = null;
        });
      } else if (photo != null)
        setState(() => _workingPhoto = photo);
      else if (!_unknown)
        _operation = null;
    }
  }

  Future<void> _open([String? id]) async {
    if (!_active || _busy || _loading || _unknown || (id == null && !_editable))
      return;
    final epoch = _epoch;
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      if (id == null) {
        final group = _configuration!.groups.firstWhere((g) => g.id == _group);
        if (group.sources.isEmpty)
          throw ApiException(
            'Ask your teacher to publish learning material for this subject.',
            isApiMessage: true,
          );
        _creation ??= TutorSessionWrite(
          operationId: const Uuid().v4(),
          groupId: group.id,
          sourceVersionIds: group.sources
              .take(3)
              .map((source) => source.id)
              .toSet(),
        );
        final value = await widget.repository.create(_creation!);
        if (!_current(epoch)) return;
        id = value.id;
        _creation = null;
        _sessionId = id;
      }
      final detail = await _latest(id);
      if (!_current(epoch)) return;
      setState(() {
        _sessionId = id;
        _detail = detail;
        _busy = false;
        _operation = null;
        _unknown = false;
        _question.clear();
        _working.clear();
        _workingPhoto = null;
      });
      _recover(detail);
    } catch (error) {
      if (_current(epoch)) {
        setState(() => _busy = false);
        _failed(error);
      }
    }
  }

  Future<void> _send({bool retry = false}) async {
    if (!_active ||
        !(widget.canWrite?.call() ?? true) ||
        _busy ||
        _sessionId == null ||
        _detail?.state.value != 'open')
      return;
    if (!retry && (!_editable || _question.text.trim().isEmpty)) return;
    final intent = retry
        ? _intent
        : TutorTurnIntent(
            _sessionId!,
            TutorTurnWrite(
              operationId: _operation ?? const Uuid().v4(),
              question: _question.text.trim(),
              working: _working.text,
              photoIds: _workingPhoto == null
                  ? <String>{}
                  : {_workingPhoto!.id},
            ),
          );
    if (intent == null) return;
    _operation = intent.operation;
    _intent = intent;
    final epoch = _epoch;
    setState(() {
      _busy = true;
      _unknown = true;
      _error = null;
    });
    try {
      final result = await widget.repository.send(intent);
      if (!_current(epoch)) return;
      setState(() {
        _unknown = result.state.value == 'pending';
        _busy = false;
        if (!_unknown) {
          _clearTurnDraft();
        }
      });
      await _load();
    } catch (error) {
      if (_current(epoch)) {
        setState(() {
          _busy = false;
          _unknown = true;
        });
        _failed(error);
      }
    }
  }

  Future<void> _check() async {
    if (!_active || _busy || _sessionId == null || _operation == null) return;
    final epoch = _epoch;
    setState(() => _busy = true);
    try {
      if (_pendingUpload) {
        final detail = await _latest(_sessionId!);
        if (!_current(epoch)) return;
        setState(() {
          _detail = detail;
          _busy = false;
        });
        _recover(detail);
        return;
      }
      final result = await widget.repository.status(_sessionId!, _operation!);
      if (!_current(epoch)) return;
      setState(() {
        _busy = false;
        _unknown = result.state.value == 'pending';
        if (!_unknown) {
          _clearTurnDraft();
        }
      });
      await _load();
    } catch (error) {
      if (_current(epoch)) {
        setState(() => _busy = false);
        _failed(error);
      }
    }
  }

  Future<void> _pick(bool camera) async {
    if (!_editable || _sessionId == null) return;
    final repository = widget.repository, session = _sessionId;
    _picking = true;
    try {
      final bytes = await (camera
          ? widget.selectCameraPhoto!
          : widget.selectPhoto ?? selectReportPhoto)();
      if (!mounted ||
          !identical(repository, widget.repository) ||
          !repository.isActive ||
          session != _sessionId)
        return;
      if (WidgetsBinding.instance.lifecycleState != null &&
          WidgetsBinding.instance.lifecycleState != AppLifecycleState.resumed)
        return;
      _suspended = false;
      await _load();
      if (!_active || session != _sessionId) return;
      if (bytes != null)
        setState(() {
          _picked = bytes;
          _operation ??= const Uuid().v4();
        });
    } catch (error) {
      if (mounted && repository.isActive) _failed(error);
    } finally {
      _picking = false;
    }
  }

  Future<void> _upload(TutorPhotoIntent intent) async {
    if (!_active || !(widget.canWrite?.call() ?? true) || _busy) return;
    final epoch = _epoch;
    _uploadIntent = intent;
    _pendingUpload = true;
    _operation = intent.operation;
    setState(() {
      _busy = true;
      _error = null;
      _picked = null;
    });
    try {
      final result = await widget.repository.upload(intent);
      if (!_current(epoch)) return;
      setState(() {
        _workingPhoto = result;
        _uploadIntent = null;
        _busy = false;
        _unknown = false;
        _pendingUpload = false;
      });
    } catch (error) {
      if (_current(epoch)) {
        setState(() {
          _busy = false;
          _unknown = true;
        });
        _failed(error);
      }
    }
  }

  Future<void> _close() async {
    if (!_editable || _sessionId == null) return;
    final epoch = _epoch;
    setState(() => _busy = true);
    try {
      _closing ??= const Uuid().v4();
      await widget.repository.close(_sessionId!, _closing!);
      if (!_current(epoch)) return;
      setState(() {
        _sessionId = null;
        _detail = null;
        _busy = false;
        _closing = null;
      });
      await _load();
    } catch (error) {
      if (_current(epoch)) {
        setState(() => _busy = false);
        _failed(error);
      }
    }
  }

  Future<void> _remove(TutorPhotoReceipt photo) async {
    if (!_editable || _sessionId == null) return;
    final epoch = _epoch;
    setState(() => _busy = true);
    try {
      await widget.repository.revoke(_sessionId!, photo.id, const Uuid().v4());
      if (!_current(epoch)) return;
      setState(() {
        _workingPhoto = null;
        _operation = null;
        _busy = false;
      });
      await _load();
    } catch (error) {
      if (_current(epoch)) {
        setState(() => _busy = false);
        _failed(error);
      }
    }
  }

  Widget _turn(TutorTurn turn) => Card(
    child: Padding(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          if (turn.question != null) ...[
            const AcademicText(
              'Your question',
              style: TextStyle(fontWeight: FontWeight.bold),
            ),
            Text(turn.question!),
            const SizedBox(height: 8),
          ],
          if (turn.working?.isNotEmpty == true) ...[
            const AcademicText('Your working'),
            Text(turn.working!),
            const SizedBox(height: 8),
          ],
          if (turn.response != null) ...[
            const AcademicText(
              'Tutor feedback — for practice',
              style: TextStyle(fontWeight: FontWeight.bold),
            ),
            Text(turn.response!.text),
            const SizedBox(height: 8),
            Text(turn.response!.question),
          ] else if (turn.state.value == 'quarantined')
            AcademicText(
              turn.supportMessage ??
                  'This reply is withheld. Talk to a trusted adult for help.',
            )
          else
            AcademicText(tutorFailureText(turn.failureCode)),
        ],
      ),
    ),
  );
  @override
  Widget build(BuildContext context) {
    final configuration = _configuration, detail = _detail;
    return ScreenScaffold(
      title: l('Ask your tutor'),
      action: IconButton(
        tooltip: l('Refresh'),
        onPressed: _busy ? null : _load,
        icon: const Icon(Icons.refresh),
      ),
      body: !_active
          ? const Center(
              child: AcademicText('Reopen the tutor to check your access.'),
            )
          : _loading
          ? const Center(child: CircularProgressIndicator())
          : ListView(
              padding: const EdgeInsets.all(16),
              children: [
                if (_error != null)
                  Card(
                    child: Padding(
                      padding: const EdgeInsets.all(16),
                      child: Text(_error!),
                    ),
                  ),
                if (configuration != null && detail == null) ...[
                  const AcademicText(
                    'Choose a subject and start with your working.',
                  ),
                  const SizedBox(height: 12),
                  if (configuration.groups.isEmpty)
                    const AcademicText(
                      'No current subjects have been shared with this account.',
                    )
                  else
                    DropdownButtonFormField<int>(
                      initialValue: _group,
                      isExpanded: true,
                      decoration: InputDecoration(labelText: l('Subject')),
                      items: configuration.groups
                          .map(
                            (group) => DropdownMenuItem(
                              value: group.id,
                              child: Text(group.subjectName ?? group.name),
                            ),
                          )
                          .toList(),
                      onChanged: _editable && _creation == null
                          ? (value) => setState(() => _group = value)
                          : null,
                    ),
                  const SizedBox(height: 12),
                  FilledButton(
                    onPressed: _editable && configuration.groups.isNotEmpty
                        ? () => _open()
                        : null,
                    child: const AcademicText('Start a learning conversation'),
                  ),
                  for (final session
                      in _sessions?.sessions ?? <TutorSessionListItem>[])
                    ListTile(
                      title: Text(
                        configuration.groups
                                .where((g) => g.subjectId == session.subjectId)
                                .firstOrNull
                                ?.subjectName ??
                            l('Learning conversation'),
                      ),
                      subtitle: AcademicText(
                        session.state.value == 'closed'
                            ? 'Closed conversation'
                            : 'Continue learning',
                      ),
                      trailing: const Icon(Icons.chevron_right),
                      onTap: _active && !_busy && !_loading && !_unknown
                          ? () => _open(session.id)
                          : null,
                    ),
                ],
                if (detail != null) ...[
                  const AcademicText(
                    'Hints help you learn. Feedback is for practice and does not change your school marks.',
                  ),
                  const SizedBox(height: 12),
                  ...detail.turns.map(_turn),
                  if (_unknown)
                    Card(
                      child: Padding(
                        padding: const EdgeInsets.all(16),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.stretch,
                          children: [
                            const AcademicText(
                              'Check the existing turn before sending another. Your exact request is kept by the school.',
                            ),
                            Wrap(
                              spacing: 12,
                              runSpacing: 8,
                              children: [
                                TextButton(
                                  onPressed: _busy ? null : _check,
                                  child: const AcademicText('Check reply'),
                                ),
                                if (_intent != null)
                                  TextButton(
                                    onPressed: _busy
                                        ? null
                                        : () => _send(retry: true),
                                    child: const AcademicText(
                                      'Retry the same turn',
                                    ),
                                  ),
                                if (_uploadIntent != null)
                                  TextButton(
                                    onPressed: _busy
                                        ? null
                                        : () => _upload(_uploadIntent!),
                                    child: const AcademicText(
                                      'Retry this working upload',
                                    ),
                                  ),
                              ],
                            ),
                          ],
                        ),
                      ),
                    ),
                  if (_picked != null &&
                      _sessionId != null &&
                      _operation != null)
                    TutorWorkingCrop(
                      key: ValueKey(_epoch),
                      bytes: _picked!,
                      session: _sessionId!,
                      operation: _operation!,
                      onApproved: _upload,
                      onCancel: () => setState(() => _picked = null),
                    ),
                  if (!(widget.canWrite?.call() ?? true))
                    const AcademicText(
                      'This school year is read only. Existing conversations are still available.',
                    ),
                  if (!_unknown &&
                      _picked == null &&
                      detail.state.value == 'open') ...[
                    if (configuration?.inferenceEnabled != true)
                      const AcademicText(
                        'Your school is preparing the tutor. Try again when it is available.',
                      ),
                    Wrap(
                      spacing: 12,
                      runSpacing: 8,
                      children: [
                        if (widget.selectCameraPhoto != null)
                          OutlinedButton.icon(
                            onPressed: _editable ? () => _pick(true) : null,
                            icon: const Icon(Icons.photo_camera_outlined),
                            label: const AcademicText('Take a working photo'),
                          ),
                        OutlinedButton.icon(
                          onPressed: _editable ? () => _pick(false) : null,
                          icon: const Icon(Icons.add_photo_alternate_outlined),
                          label: const AcademicText('Choose a working photo'),
                        ),
                      ],
                    ),
                    if (_workingPhoto != null) ...[
                      const AcademicText(
                        'Your approved working crop is ready for this turn.',
                      ),
                      _TutorPhotoPreview(
                        key: ValueKey(_workingPhoto!.id),
                        repository: widget.repository,
                        session: _sessionId!,
                        photo: _workingPhoto!,
                      ),
                      TextButton(
                        onPressed: _editable
                            ? () => _remove(_workingPhoto!)
                            : null,
                        child: const AcademicText('Remove this working crop'),
                      ),
                    ],
                    for (final photo in detail.pendingPhotos.where(
                      (p) => _operation == null || p.operationId == _operation,
                    ))
                      Wrap(
                        spacing: 12,
                        children: [
                          if (!detail.turns.any(
                            (turn) => turn.operationId == photo.operationId,
                          ))
                            TextButton(
                              onPressed: _editable
                                  ? () => setState(() {
                                      _workingPhoto = photo;
                                      _operation = photo.operationId;
                                    })
                                  : null,
                              child: const AcademicText(
                                'Use the working already uploaded for this turn',
                              ),
                            ),
                          TextButton(
                            onPressed: _editable ? () => _remove(photo) : null,
                            child: const AcademicText(
                              'Remove unused working crop',
                            ),
                          ),
                        ],
                      ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: _question,
                      enabled: _editable,
                      maxLength: 1000,
                      minLines: 2,
                      maxLines: 4,
                      decoration: InputDecoration(
                        labelText: l('Your question'),
                      ),
                    ),
                    TextField(
                      controller: _working,
                      enabled: _editable,
                      maxLength: 2000,
                      minLines: 2,
                      maxLines: 5,
                      decoration: InputDecoration(
                        labelText: l('Show what you tried'),
                      ),
                    ),
                    const SizedBox(height: 12),
                    FilledButton(
                      onPressed:
                          _editable && configuration?.inferenceEnabled == true
                          ? () => _send()
                          : null,
                      child: const AcademicText('Ask for a hint'),
                    ),
                  ],
                  const SizedBox(height: 12),
                  TextButton(
                    onPressed: _editable ? _close : null,
                    child: const AcademicText('Close this conversation'),
                  ),
                ],
                if (_busy)
                  const Padding(
                    padding: EdgeInsets.all(16),
                    child: Center(child: CircularProgressIndicator()),
                  ),
              ],
            ),
    );
  }
}

class _TutorPhotoPreview extends StatefulWidget {
  const _TutorPhotoPreview({
    required this.repository,
    required this.session,
    required this.photo,
    super.key,
  });
  final TutorRepository repository;
  final String session;
  final TutorPhotoReceipt photo;
  @override
  State<_TutorPhotoPreview> createState() => _TutorPhotoPreviewState();
}

class _TutorPhotoPreviewState extends State<_TutorPhotoPreview> {
  String? _image;
  bool _failed = false;
  @override
  void initState() {
    super.initState();
    unawaited(_load());
  }

  Future<void> _load() async {
    try {
      final bytes = await widget.repository.preview(
        widget.session,
        widget.photo,
      );
      if (!mounted || !widget.repository.isActive) return;
      setState(() => _image = base64Encode(bytes));
    } catch (_) {
      if (mounted) setState(() => _failed = true);
    }
  }

  @override
  void dispose() {
    _image = null;
    super.dispose();
  }

  @override
  Widget build(BuildContext context) => _image != null
      ? SizedBox(
          height: 180,
          child: PrivateReportImage(
            base64: _image!,
            maximumEdge: 768,
            label: academicText(context, 'Your approved working crop'),
          ),
        )
      : _failed
      ? const AcademicText('This working crop is no longer available.')
      : const LinearProgressIndicator();
}
