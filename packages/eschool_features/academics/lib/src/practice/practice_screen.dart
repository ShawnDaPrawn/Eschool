import 'dart:async';
import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:uuid/uuid.dart';
import '../localization/academic_text.dart';
import 'practice_answers.dart';
import 'practice_cache.dart';
import 'practice_repository.dart';

/// Own self-study only; official marks are never written by this reader.
class PracticeScreen extends StatefulWidget {
  const PracticeScreen({
    required this.repository,
    required this.cache,
    required this.canRead,
    required this.canWrite,
    super.key,
  });
  final PracticeRepository repository;
  final PracticeCache cache;
  final bool Function() canRead, canWrite;
  @override
  State<PracticeScreen> createState() => _PracticeScreenState();
}

class _PracticeScreenState extends State<PracticeScreen>
    with WidgetsBindingObserver {
  PracticeAccess? _access;
  PracticeDailySet? _set;
  PracticeIntent? _pending;
  List<PracticeIntent> _queued = [];
  int? _group;
  int _index = 0, _epoch = 0, _rating = 3;
  bool _busy = true,
      _dead = false,
      _suspended = false,
      _saved = false,
      _savedWritable = false;
  bool? _correct;
  String _error = '', _status = '', _answer = '';
  final _number = TextEditingController();
  final _scroll = ScrollController();
  String l(String text) => academicText(context, text);
  bool get _active =>
      mounted &&
      !_dead &&
      !_suspended &&
      widget.canRead() &&
      widget.repository.isActive;
  bool get _write =>
      _active &&
      widget.canWrite() &&
      (_access?.writable ?? (_saved && _savedWritable));
  bool _current(int epoch) => _active && _epoch == epoch;
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _load();
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    _scroll.dispose();
    _epoch++;
    widget.cache.close();
    _number.dispose();
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (!mounted) return;
    if (state != AppLifecycleState.resumed) {
      setState(() {
        _suspended = true;
        _epoch++;
        _set = null;
        _access = null;
        _pending = null;
        _queued = [];
        _answer = '';
        _number.clear();
        _correct = null;
        _busy = false;
      });
    } else if (_suspended && !_dead) {
      _suspended = false;
      _load();
    }
  }

  void _fail(Object error, int epoch) {
    if (!mounted || epoch != _epoch) return;
    if (error is ApiException && [401, 403, 404].contains(error.statusCode)) {
      unawaited(widget.cache.revoke().catchError((Object _) {}));
    }
    setState(() {
      _busy = false;
      if (error is ApiException && [401, 403, 404].contains(error.statusCode)) {
        _dead = true;
        _set = null;
        _access = null;
        _pending = null;
        _queued = [];
        _answer = '';
        _number.clear();
        _correct = null;
        _error = 'Reopen practice to check your access.';
      } else if (error is ApiException &&
          [409, 422].contains(error.statusCode)) {
        _pending = null;
        _error =
            'This operation was refused. Refresh access or remove the conflicting saved answer before continuing.';
      } else if (error is FormatException) {
        _error = error.message;
      } else {
        _error =
            'Practice could not be refreshed. Saved answers keep their exact retry.';
      }
    });
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted && epoch == _epoch && _scroll.hasClients) {
        _scroll.jumpTo(0);
      }
    });
  }

  Future<void> _load() async {
    if (!_active || _pending != null) return;
    final epoch = ++_epoch;
    setState(() {
      _busy = true;
      _error = '';
      _set = null;
      _correct = null;
    });
    try {
      final access = await widget.repository.access();
      if (!_current(epoch)) return;
      await widget.cache.retainGroups(
        access.learnerGroups.map((g) => g.id).toSet(),
      );
      if (!_current(epoch)) return;
      setState(() {
        _access = access;
        _busy = false;
        if (!access.learnerGroups.any((g) => g.id == _group)) _group = null;
      });
    } catch (e) {
      _fail(e, epoch);
    }
  }

  void _resetAnswer() {
    _answer = '';
    _number.clear();
    _correct = null;
    _rating = 3;
  }

  Future<void> _open(PracticeGroupChoice group) async {
    if (!_active || _busy || _pending != null) return;
    final access = _access;
    if (access?.learnerId == null) return;
    final intent = group.setId == null
        ? PracticeIntent.issue(
            access!.learnerId!,
            group.id,
            PracticeIssueInput(
              operationId: const Uuid().v4(),
              practiceOn: access.practiceOn,
            ),
          )
        : null;
    if (intent != null && !_write) return;
    await _openIntent(group, intent);
  }

  Future<void> _openIntent(
    PracticeGroupChoice group,
    PracticeIntent? intent,
  ) async {
    if (!_active || _busy || (intent != null && !_write)) return;
    final epoch = _epoch;
    setState(() {
      _busy = true;
      _error = '';
      _pending = intent;
      _group = group.id;
    });
    try {
      final value = intent == null
          ? await widget.repository.daily(_access!.learnerId!, group.setId!)
          : await widget.repository.issue(intent);
      if (!_current(epoch)) return;
      _pending = null;
      final saved = await widget.cache.read(value.learnerId, value.id);
      if (!_current(epoch)) return;
      setState(() {
        _set = value;
        _index = 0;
        _queued = saved?.pending ?? [];
        _saved = saved != null;
        _savedWritable = saved?.writable ?? false;
        _busy = false;
        _status = '';
        _resetAnswer();
      });
    } catch (e) {
      _fail(e, epoch);
    }
  }

  Future<void> _save() async {
    if (!_active || _busy || _set == null || _pending != null) return;
    final value = _set!, epoch = _epoch;
    setState(() {
      _busy = true;
      _error = '';
    });
    try {
      final started = DateTime.now().toUtc();
      final fresh = await widget.repository.daily(value.learnerId, value.id);
      if (!_current(epoch)) return;
      await widget.cache.save(
        fresh,
        requestedAt: started,
        writable: _access?.writable == true && widget.canWrite(),
      );
      if (!_current(epoch)) return;
      setState(() {
        _set = fresh;
        _saved = true;
        _savedWritable = _access?.writable == true && widget.canWrite();
        _busy = false;
        _status = 'Encrypted practice copy saved for offline use.';
      });
    } catch (e) {
      _fail(e, epoch);
    }
  }

  Future<void> _answerQuestion() async {
    if (!_write || _busy || _set == null || _pending != null) return;
    final value = _set!, entry = value.items[_index], epoch = _epoch;
    if (entry.review != null || _queued.any((p) => p.entry == entry.id)) return;
    try {
      final answer = entry.definition.kind == PracticeQuestionKindEnum.number
          ? _number.text.trim()
          : _answer;
      final correct = LocalPracticeAnswers.correct(entry.definition, answer);
      final intent = PracticeIntent.review(
        value.learnerId,
        value.id,
        entry.id,
        PracticeReviewInput(
          operationId: const Uuid().v4(),
          answer: answer,
          rating: _rating,
        ),
      );
      setState(() {
        _busy = true;
        _error = '';
      });
      if (_saved) {
        await widget.cache.queue(intent);
        if (!_current(epoch)) return;
        setState(() {
          _queued = [..._queued, intent];
          _correct = correct;
          _busy = false;
          _status =
              'Answer saved on this device. Sync to update your study schedule.';
        });
      } else {
        _pending = intent;
        await _sendReview(intent, epoch);
      }
    } catch (e) {
      _fail(e, epoch);
    }
  }

  Future<void> _sendReview(PracticeIntent intent, int epoch) async {
    final review = await widget.repository.review(intent);
    if (!_current(epoch)) return;
    // The write is confirmed even if the subsequent fresh read fails.
    setState(() {
      _pending = null;
      _correct = review.correct;
      _status = 'Answer synchronized.';
    });
    try {
      final value = await widget.repository.daily(intent.learner!, intent.set!);
      if (!_current(epoch)) return;
      setState(() {
        _set = value;
        _busy = false;
      });
    } catch (e) {
      _fail(e, epoch);
    }
  }

  Future<void> _sync() async {
    if (!_active || _busy || _set == null || _pending != null) return;
    final value = _set!, epoch = _epoch;
    setState(() {
      _busy = true;
      _error = '';
    });
    try {
      final updated = await widget.cache.synchronize(value.learnerId, value.id);
      final saved = await widget.cache.read(value.learnerId, value.id);
      if (!_current(epoch)) return;
      setState(() {
        _set = updated;
        _saved = saved != null;
        _savedWritable = saved?.writable ?? false;
        if (_access != null) {
          _access = PracticeAccess.fromJson({
            ..._access!.toJson(),
            'writable': _savedWritable,
          });
        }
        _queued = saved?.pending ?? [];
        _busy = false;
        _status = _queued.isEmpty
            ? 'Answers synchronized.'
            : 'Practice is read only. Saved answers are waiting.';
      });
    } catch (e) {
      _fail(e, epoch);
    }
  }

  Future<void> _removePending(PendingPractice row) async {
    if (!_active || _busy) return;
    final epoch = _epoch;
    try {
      await widget.cache.remove(row.learner, row.set);
      if (_current(epoch)) {
        setState(() {
          _status = 'Offline copy removed.';
        });
      }
    } catch (error) {
      _fail(error, epoch);
    }
  }

  Future<void> _syncPending(PendingPractice row) async {
    if (!_active || _busy) return;
    final epoch = _epoch;
    setState(() {
      _busy = true;
      _error = '';
    });
    try {
      final value = await widget.cache.synchronize(row.learner, row.set);
      if (!_current(epoch)) return;
      final saved = await widget.cache.read(row.learner, row.set);
      if (!_current(epoch)) return;
      setState(() {
        _set = value;
        _saved = saved != null;
        _savedWritable = saved?.writable ?? false;
        if (_access != null) {
          _access = PracticeAccess.fromJson({
            ..._access!.toJson(),
            'writable': _savedWritable,
          });
        }
        _queued = saved?.pending ?? [];
        _index = 0;
        _busy = false;
        _resetAnswer();
        _status = 'Answers synchronized.';
      });
    } catch (error) {
      _fail(error, epoch);
    }
  }

  Future<void> _savedCopies() async {
    if (!_active || _busy || _pending != null) return;
    final epoch = _epoch;
    setState(() {
      _busy = true;
      _error = '';
    });
    try {
      final copies = await widget.cache.saved();
      final pending = await widget.cache.pending();
      if (!_current(epoch)) return;
      setState(() {
        _busy = false;
      });
      if (!mounted) return;
      final chosen = await showDialog<SavedPractice>(
        context: context,
        builder: (context) => SimpleDialog(
          title: AcademicText('Saved practice'),
          children: [
            for (final copy in copies)
              SimpleDialogOption(
                onPressed: () => Navigator.pop(context, copy),
                child: Text(
                  '${copy.set.practiceOn} · ${copy.set.items.length} ${l('questions')}',
                ),
              ),
            for (final row in pending.where(
              (row) => !copies.any((c) => c.set.id == row.set),
            ))
              SimpleDialogOption(
                onPressed: () {
                  Navigator.pop(context);
                  _syncPending(row);
                },
                child: Text('${l('Sync saved answers')} (${row.count})'),
              ),
            for (final row in pending.where(
              (row) => !copies.any((c) => c.set.id == row.set),
            ))
              SimpleDialogOption(
                onPressed: () {
                  Navigator.pop(context);
                  _removePending(row);
                },
                child: Text(
                  '${l('Remove expired copy and pending answers')} (${row.count})',
                ),
              ),
            if (copies.isEmpty && pending.isEmpty)
              Padding(
                padding: const EdgeInsets.all(16),
                child: AcademicText('No current offline copies.'),
              ),
          ],
        ),
      );
      if (!_current(epoch) || chosen == null) return;
      setState(() {
        _set = chosen.set;
        _queued = chosen.pending;
        _saved = true;
        _savedWritable = chosen.writable;
        _index = 0;
        _resetAnswer();
        _status = 'Offline copy. Sync requires a fresh access check.';
      });
    } catch (e) {
      _fail(e, epoch);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (!_active) {
      return ScreenScaffold(
        title: l('Daily practice'),
        body: Center(
          child: AcademicText('Reopen practice to check your access.'),
        ),
      );
    }
    final value = _set;
    final entry = value == null ? null : value.items[_index];
    final answered =
        entry != null &&
        (entry.review != null ||
            _queued.any((p) => p.entry == entry.id) ||
            _correct != null);
    return ScreenScaffold(
      title: l('Daily practice'),
      body: ListView(
        controller: _scroll,
        padding: const EdgeInsets.all(16),
        children: [
          AcademicText(
            'Short, unranked practice. Your answers update your study schedule, not official marks.',
          ),
          if (_busy) const LinearProgressIndicator(),
          if (_error.isNotEmpty)
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 12),
              child: Text(
                l(_error),
                style: TextStyle(color: Theme.of(context).colorScheme.error),
              ),
            ),
          if (_status.isNotEmpty)
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 8),
              child: Text(l(_status)),
            ),
          if (_pending != null) ...[
            AcademicText(
              'The outcome is unknown. Retry this exact saved operation before changing it.',
            ),
            FilledButton(
              onPressed: !_busy && _write
                  ? () async {
                      final intent = _pending!;
                      if (intent.action == PracticeAction.issue) {
                        final group = _access!.learnerGroups.firstWhere(
                          (g) => g.id == intent.group,
                        );
                        await _openIntent(group, intent);
                      } else {
                        setState(() {
                          _busy = true;
                        });
                        try {
                          await _sendReview(intent, _epoch);
                        } catch (e) {
                          _fail(e, _epoch);
                        }
                      }
                    }
                  : null,
              child: AcademicText('Retry exact operation'),
            ),
          ],
          if (value == null && _pending == null) ...[
            for (final group
                in _access?.learnerGroups ?? <PracticeGroupChoice>[])
              Card(
                child: ListTile(
                  title: Text('${group.subjectName} · ${group.name}'),
                  subtitle: Text(
                    l(
                      group.setId == null
                          ? 'Start a ten-minute set'
                          : 'Resume today’s set',
                    ),
                  ),
                  onTap: !_busy && (group.setId != null || _write)
                      ? () => _open(group)
                      : null,
                ),
              ),
            OutlinedButton(
              onPressed: !_busy ? _load : null,
              child: AcademicText('Refresh access'),
            ),
            TextButton(
              onPressed: !_busy ? _savedCopies : null,
              child: AcademicText('Open saved practice'),
            ),
            if (_access != null && _access!.learnerGroups.isEmpty)
              AcademicText(
                'No approved learner practice groups are available.',
              ),
          ],
          if (value != null && entry != null) ...[
            Text(
              '${_index + 1}/${value.items.length} · ${(value.estimatedSeconds / 60).ceil()} ${l('minutes')} · ${value.streakDays} ${l('study days')}',
              style: Theme.of(context).textTheme.titleMedium,
            ),
            const SizedBox(height: 16),
            Text(
              entry.definition.prompt,
              style: Theme.of(context).textTheme.titleLarge,
            ),
            if (!answered) ...[
              if (entry.definition.kind == PracticeQuestionKindEnum.number)
                TextField(
                  controller: _number,
                  enabled: _write && !_busy && _pending == null,
                  keyboardType: const TextInputType.numberWithOptions(
                    decimal: true,
                    signed: true,
                  ),
                  decoration: InputDecoration(
                    labelText: l('Your decimal answer'),
                  ),
                  maxLength: 100,
                ),
              if (entry.definition.kind == PracticeQuestionKindEnum.choice)
                RadioGroup<String>(
                  groupValue: _answer,
                  onChanged: (answer) {
                    if (_write && !_busy && _pending == null) {
                      setState(() {
                        _answer = answer ?? '';
                      });
                    }
                  },
                  child: Column(
                    children: [
                      for (final option in entry.definition.options)
                        RadioListTile<String>(
                          value: option.id,
                          title: Text(option.text),
                          enabled: _write && !_busy && _pending == null,
                        ),
                    ],
                  ),
                ),
              DropdownButtonFormField<int>(
                initialValue: _rating,
                decoration: InputDecoration(labelText: l('How did it feel?')),
                items: [
                  DropdownMenuItem(value: 2, child: AcademicText('Hard')),
                  DropdownMenuItem(value: 3, child: AcademicText('Good')),
                  DropdownMenuItem(value: 4, child: AcademicText('Easy')),
                ],
                onChanged: _write && !_busy && _pending == null
                    ? (value) => setState(() {
                        _rating = value ?? 3;
                      })
                    : null,
              ),
              FilledButton(
                onPressed: _write && !_busy && _pending == null
                    ? _answerQuestion
                    : null,
                child: AcademicText('Check answer'),
              ),
            ] else ...[
              Text(
                l(
                  (_correct ??
                          entry.review?.correct ??
                          LocalPracticeAnswers.correct(
                            entry.definition,
                            _queued
                                    .firstWhere((p) => p.entry == entry.id)
                                    .body['answer']
                                as String,
                          ))
                      ? 'Correct'
                      : 'Review the worked answer',
                ),
              ),
              Text(entry.definition.explanation),
            ],
            Row(
              children: [
                Expanded(
                  child: OutlinedButton(
                    onPressed: !_busy && _pending == null && _index > 0
                        ? () => setState(() {
                            _index--;
                            _resetAnswer();
                          })
                        : null,
                    child: AcademicText('Previous'),
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: OutlinedButton(
                    onPressed:
                        !_busy &&
                            _pending == null &&
                            _index + 1 < value.items.length
                        ? () => setState(() {
                            _index++;
                            _resetAnswer();
                          })
                        : null,
                    child: AcademicText('Next'),
                  ),
                ),
              ],
            ),
            if (_saved)
              FilledButton.tonal(
                onPressed: !_busy && _pending == null ? _sync : null,
                child: Text('${l('Sync saved answers')} (${_queued.length})'),
              ),
            if (!_saved)
              OutlinedButton(
                onPressed: !_busy && _pending == null ? _save : null,
                child: AcademicText('Save for offline'),
              ),
            if (_saved)
              TextButton(
                onPressed: !_busy && _pending == null
                    ? () async {
                        final epoch = _epoch;
                        await widget.cache.remove(value.learnerId, value.id);
                        if (_current(epoch)) {
                          setState(() {
                            _saved = false;
                            _queued = [];
                            _status = 'Offline copy removed.';
                          });
                        }
                      }
                    : null,
                child: AcademicText('Remove offline copy and pending answers'),
              ),
            TextButton(
              onPressed: !_busy && _pending == null ? _load : null,
              child: AcademicText('Choose another subject'),
            ),
          ],
        ],
      ),
    );
  }
}
