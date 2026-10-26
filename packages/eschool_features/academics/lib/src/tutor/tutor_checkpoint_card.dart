import 'dart:async';

import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:uuid/uuid.dart';

import '../localization/academic_text.dart';
import '../private/private_pending_intent.dart';
import 'tutor_repository.dart';

/// Only opaque locators and a bounded encrypted submitted intent survive a UI
/// erasure. The parent closes this state on account/session disposal.
class TutorCheckpointRecovery {
  String? session, issueOperation, checkpoint, answerOperation;
  PrivatePendingIntent? pending;
  bool get unresolved =>
      pending != null || (issueOperation != null && checkpoint == null);
  void close() {
    pending?.close();
    pending = null;
    session = null;
    issueOperation = null;
    checkpoint = null;
    answerOperation = null;
  }
}

class TutorCheckpointCard extends StatefulWidget {
  const TutorCheckpointCard({
    required this.repository,
    required this.owner,
    required this.session,
    required this.recovery,
    required this.current,
    required this.writable,
    required this.summaries,
    super.key,
  });
  final TutorCheckpointRepository repository;
  final TutorRepository owner;
  final String session;
  final TutorCheckpointRecovery recovery;
  final bool Function() current, writable;
  final List<TutorCheckpointSummary> summaries;
  @override
  State<TutorCheckpointCard> createState() => _TutorCheckpointCardState();
}

class _TutorCheckpointCardState extends State<TutorCheckpointCard>
    with WidgetsBindingObserver {
  final _answer = TextEditingController();
  TutorCheckpointDetail? _detail;
  String? _error;
  bool _busy = false, _hidden = false, _denied = false;
  int _epoch = 0;
  TutorCheckpointRecovery get recovery => widget.recovery;
  bool get _current =>
      mounted &&
      !_hidden &&
      !_denied &&
      widget.owner.isActive &&
      widget.current();
  bool _valid(int epoch) => _current && epoch == _epoch;
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    if (recovery.session == widget.session && recovery.checkpoint != null)
      unawaited(_read());
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    _epoch++;
    _answer.dispose();
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state != AppLifecycleState.resumed) {
      _epoch++;
      setState(() {
        _hidden = true;
        _detail = null;
        _answer.clear();
        _error = null;
        _busy = false;
      });
    } else if (_hidden) {
      setState(() => _hidden = false);
      if (recovery.checkpoint != null) unawaited(_read());
    }
  }

  Future<void> _run(Future<void> Function(int) action) async {
    if (!_current || _busy) return;
    final epoch = _epoch;
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      await action(epoch);
    } catch (e) {
      if (!_valid(epoch)) return;
      if (e is ApiException && [401, 403].contains(e.statusCode)) {
        recovery.close();
        _detail = null;
        _answer.clear();
        _denied = true;
        _error = 'Reopen the tutor to check your access.';
      } else {
        // A racing 404 does not authorize a replacement issue/answer UUID.
        if (e is ApiException && [404, 409].contains(e.statusCode)) {
          _detail = null;
          _answer.clear();
        }
        _error = e is ApiException && e.isApiMessage
            ? e.errorMessage
            : 'This check is not confirmed. Check its status before starting another.';
      }
    } finally {
      if (_valid(epoch))
        setState(() => _busy = false);
      else if (mounted)
        setState(() {});
    }
  }

  void _take(TutorCheckpointDetail value, int epoch) {
    if (!_valid(epoch)) return;
    recovery.checkpoint = value.id;
    recovery.issueOperation = value.operationId;
    _detail = value;
    _answer.clear();
    if (value.state.value == 'answered') {
      recovery.pending?.close();
      recovery.pending = null;
    }
  }

  Future<void> _issue() => _run((epoch) async {
    if (!widget.writable()) return;
    if (recovery.session != null &&
        recovery.session != widget.session &&
        recovery.unresolved) {
      _error = 'Check the existing topic before starting another.';
      return;
    }
    if (recovery.session != widget.session) recovery.close();
    recovery.session = widget.session;
    recovery.issueOperation ??= const Uuid().v4();
    _take(
      await widget.repository.issueCheckpoint(
        widget.session,
        recovery.issueOperation!,
      ),
      epoch,
    );
  });
  Future<void> _read() => _run((epoch) async {
    if (recovery.session != widget.session || recovery.checkpoint == null)
      return;
    _take(
      await widget.repository.checkpoint(widget.session, recovery.checkpoint!),
      epoch,
    );
  });
  Future<void> _submit() => _run((epoch) async {
    if (!widget.writable() || recovery.checkpoint == null) return;
    // Fresh server/source authorization precedes opening retained answer prose.
    final fresh = await widget.repository.checkpoint(
      widget.session,
      recovery.checkpoint!,
    );
    if (!_valid(epoch)) return;
    if (fresh.state.value == 'answered') {
      _take(fresh, epoch);
      return;
    }
    if (recovery.pending == null) {
      if (recovery.answerOperation != null) {
        _error =
            'The retained answer expired. Check its status; do not replace it.';
        return;
      }
      final text = _answer.text.trim();
      if (text.isEmpty || text.length > 100) return;
      recovery.answerOperation = const Uuid().v4();
      recovery.pending = PrivatePendingIntent(
        school: widget.owner.schoolId,
        reader: widget.owner.readerId,
        body: {'operation_id': recovery.answerOperation, 'answer': text},
      );
    }
    if (recovery.pending!.expired) {
      _error =
          'The retained answer expired. Check its status; do not replace it.';
      return;
    }
    final body = recovery.pending!.open(
      school: widget.owner.schoolId,
      reader: widget.owner.readerId,
      authorized: _current && widget.writable(),
    );
    try {
      _take(
        await widget.repository.answerCheckpoint(
          widget.session,
          recovery.checkpoint!,
          TutorCheckpointAnswerWrite.fromJson(body),
        ),
        epoch,
      );
    } on ApiException catch (error) {
      // A known validation refusal committed no first attempt. Let the learner
      // correct it; uncertain transport/source failures keep the exact locator.
      if (error.statusCode == 422) {
        recovery.pending?.close();
        recovery.pending = null;
        recovery.answerOperation = null;
      }
      rethrow;
    }
  });
  void _history(TutorCheckpointSummary value) {
    if (recovery.unresolved || _busy) return;
    recovery.close();
    recovery.session = widget.session;
    recovery.checkpoint = value.id;
    recovery.issueOperation = value.operationId;
    unawaited(_read());
  }

  @override
  Widget build(BuildContext context) {
    if (_denied)
      return const AcademicText('Reopen the tutor to check your access.');
    if (_hidden || !_current) return const SizedBox.shrink();
    final value = _detail;
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            const AcademicText(
              'Verified practice check',
              style: TextStyle(fontWeight: FontWeight.bold),
            ),
            const AcademicText(
              'Your first answer is checked against a teacher-approved definition. This does not change school marks.',
            ),
            if (_busy) const LinearProgressIndicator(),
            if (_error != null) Text(_error!),
            if (value == null)
              FilledButton(
                onPressed: _busy || !widget.writable() ? null : _issue,
                child: AcademicText(
                  recovery.issueOperation == null
                      ? 'Start an approved check'
                      : 'Recover the same approved check',
                ),
              ),
            if (recovery.checkpoint != null)
              TextButton(
                onPressed: _busy ? null : _read,
                child: const AcademicText('Check answer status'),
              ),
            if (value != null) ...[
              Text(value.question.prompt),
              if (value.state.value == 'ready') ...[
                if (value.question.kind.value == 'choice')
                  DropdownButtonFormField<String>(
                    key: ValueKey(value.id),
                    decoration: InputDecoration(
                      labelText: academicText(context, 'Your answer'),
                    ),
                    items: value.question.options
                        .map(
                          (o) => DropdownMenuItem(
                            value: o.id,
                            child: Text(o.text),
                          ),
                        )
                        .toList(),
                    onChanged: _busy || recovery.answerOperation != null
                        ? null
                        : (s) => _answer.text = s ?? '',
                  ),
                if (value.question.kind.value == 'number')
                  TextField(
                    controller: _answer,
                    maxLength: 100,
                    readOnly: _busy || recovery.answerOperation != null,
                    keyboardType: const TextInputType.numberWithOptions(
                      decimal: true,
                      signed: true,
                    ),
                    decoration: InputDecoration(
                      labelText: academicText(context, 'Your answer'),
                    ),
                  ),
                FilledButton(
                  onPressed: _busy || !widget.writable() ? null : _submit,
                  child: AcademicText(
                    recovery.answerOperation == null
                        ? 'Check my first answer'
                        : 'Retry exact first answer',
                  ),
                ),
              ],
              if (value.state.value == 'answered') ...[
                AcademicText(
                  value.correct == true
                      ? 'Verified correct'
                      : 'Review this worked example',
                ),
                if (value.explanation != null) Text(value.explanation!),
                FilledButton(
                  onPressed: _busy || !widget.writable()
                      ? null
                      : () {
                          recovery.close();
                          setState(() => _detail = null);
                        },
                  child: const AcademicText('Choose another approved check'),
                ),
              ],
            ],
            for (final old in widget.summaries)
              TextButton(
                onPressed: _busy || recovery.unresolved
                    ? null
                    : () => _history(old),
                child: AcademicText(
                  old.state.value == 'answered'
                      ? 'Read a completed check'
                      : 'Continue an approved check',
                ),
              ),
          ],
        ),
      ),
    );
  }
}
