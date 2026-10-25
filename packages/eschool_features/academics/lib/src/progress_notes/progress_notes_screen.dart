import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:uuid/uuid.dart';

import '../localization/academic_text.dart';
import 'progress_repository.dart';

/// Private text lives only in this route and is discarded when it is suspended.
class ProgressNotesScreen extends StatefulWidget {
  const ProgressNotesScreen({
    required this.repository,
    required this.canRead,
    super.key,
  });
  final ProgressRepository repository;
  final bool Function() canRead;
  @override
  State<ProgressNotesScreen> createState() => _ProgressNotesScreenState();
}

class _ProgressNotesScreenState extends State<ProgressNotesScreen>
    with WidgetsBindingObserver {
  final _body = TextEditingController();
  ProgressConfiguration? _config;
  List<ProgressLearnerOption> _learners = [];
  List<LearnerNoteMetadata> _notes = [];
  List<LearnerNoteRevision> _history = [];
  LearnerNoteDetail? _detail;
  ProgressStaffBand? _band;
  int? _term;
  int? _group, _learner, _subject, _year;
  int _bandEpoch = 0;
  int _epoch = 0, _page = 1, _lastPage = 1, _after = 0;
  String _mode = 'staff', _error = '';
  LearnerNoteDetail? _conflictCurrent;
  bool _busy = true, _suspended = false, _uncertain = false, _conflict = false;
  String? _writeId;
  Map<String, dynamic>? _pending;
  bool get _allowed =>
      mounted && !_suspended && widget.canRead() && widget.repository.isActive;
  bool get _clinical => _mode == 'counselling';
  bool get _assigning => _mode == 'assignments';
  bool get _editable =>
      !_assigning &&
      _year == _config?.sessionYearId &&
      (_detail == null ||
          (_detail!.authorId == widget.repository.readerId &&
              !_detail!.archived));
  List<ProgressOption> get _groupTerms {
    final ids = _config?.groups
        .where((g) => g.id == _group)
        .firstOrNull
        ?.termIds;
    // Older servers cannot prove a grade-valid term; notes remain independent.
    return ids == null
        ? []
        : (_config?.terms.where((t) => ids.contains(t.id)).toList() ?? []);
  }

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _load();
  }

  @override
  void didUpdateWidget(covariant ProgressNotesScreen oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.repository != widget.repository) {
      _config = null;
      _load();
    }
  }

  @override
  void dispose() {
    _epoch++;
    _body.clear();
    _body.dispose();
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state != AppLifecycleState.resumed) {
      _epoch++;
      _clear();
      if (mounted) {
        setState(() {
          _suspended = true;
          _config = null;
        });
      }
    } else if (mounted) {
      setState(() => _suspended = false);
      _load();
    }
  }

  void _clear() {
    _body.clear();
    _notes = [];
    _band = null;
    _history = [];
    _detail = null;
    _pending = null;
    _writeId = null;
    _uncertain = false;
    _conflict = false;
    _conflictCurrent = null;
  }

  String _message(Object error) =>
      error is ApiException && error.statusCode == 409
      ? 'This note changed. Review the latest version before saving.'
      : 'Unable to load or save. Check your access and connection, then retry.';

  void _withdraw(Object error) {
    if (error is ApiException &&
        (error.statusCode == 403 || error.statusCode == 404)) {
      _clear();
      _config = null;
      _learners = [];
      _learner = null;
    }
  }

  Future<void> _load() async {
    final epoch = ++_epoch;
    setState(() {
      _busy = true;
      _error = '';
      _clear();
      _learners = [];
    });
    try {
      final config = await widget.repository.configuration();
      if (!_allowed || epoch != _epoch) return;
      setState(() {
        _config = config;
        _year = config.sessionYearId;
        _term = null;
        _mode = config.groups.isNotEmpty
            ? 'staff'
            : config.canReadCaseload
            ? 'counselling'
            : 'assignments';
        _group = config.groups.isNotEmpty ? config.groups.first.id : null;
        _term = _groupTerms.firstOrNull?.id;
        _learner = null;
        _subject = null;
      });
      await _roster();
    } catch (error) {
      if (_allowed && epoch == _epoch) {
        setState(() {
          _withdraw(error);
          _error = _message(error);
          _busy = false;
        });
      }
    }
  }

  Future<void> _roster() async {
    final epoch = ++_epoch;
    setState(() {
      _busy = true;
      _clear();
      _learners = [];
      _learner = null;
    });
    try {
      List<ProgressLearnerOption> rows;
      int last;
      if (_clinical) {
        final r = await widget.repository.caseload(page: _page);
        rows = r.learners;
        last = r.lastPage;
      } else if (_assigning) {
        final r = await widget.repository.assignmentLearners(page: _page);
        rows = r.learners;
        last = r.lastPage;
      } else if (_group != null) {
        final r = await widget.repository.roster(_group!, page: _page);
        rows = r.learners;
        last = r.lastPage;
      } else {
        rows = [];
        last = 1;
      }
      if (!_allowed || epoch != _epoch) return;
      setState(() {
        _learners = rows;
        _lastPage = last;
        _busy = false;
      });
    } catch (error) {
      if (_allowed && epoch == _epoch) {
        setState(() {
          _withdraw(error);
          _error = _message(error);
          _busy = false;
        });
      }
    }
  }

  Future<void> _list({bool more = false}) async {
    if (_learner == null || _year == null || _assigning) return;
    final epoch = ++_epoch;
    setState(() {
      _busy = true;
      _error = '';
      _detail = null;
      _history = [];
      _body.clear();
      _pending = null;
      _uncertain = false;
      _conflict = false;
      _band = null;
      _bandEpoch++;
    });
    try {
      final result = await widget.repository.notes(
        _learner!,
        _year!,
        _mode,
        subject: _subject,
        after: more ? _after : 0,
      );
      if (!_allowed || epoch != _epoch) return;
      setState(() {
        _notes = more ? [..._notes, ...result.notes] : result.notes;
        _after = result.nextAfter ?? 0;
        _busy = false;
      });
      if (!_clinical &&
          _year == _config?.sessionYearId &&
          _term != null &&
          _group != null) {
        _loadBand(epoch);
      }
    } catch (error) {
      if (_allowed && epoch == _epoch) {
        setState(() {
          _withdraw(error);
          _error = _message(error);
          _busy = false;
          _notes = [];
        });
      }
    }
  }

  Future<void> _loadBand(int epoch) async {
    final bandEpoch = ++_bandEpoch;
    final group = _group, learner = _learner, term = _term;
    if (group == null || learner == null || term == null) return;
    bool current() =>
        _allowed &&
        epoch == _epoch &&
        bandEpoch == _bandEpoch &&
        _group == group &&
        _learner == learner &&
        _term == term;
    try {
      final band = await widget.repository.staffBand(group, learner, term);
      if (current() && band.termId == term && band.learnerId == learner) {
        setState(() => _band = band);
      }
    } catch (error) {
      if (current()) {
        setState(() {
          _band = null;
          _withdraw(error);
        });
      }
    }
  }

  Future<void> _open(LearnerNoteMetadata row) async {
    final epoch = ++_epoch;
    setState(() {
      _busy = true;
      _body.clear();
      _detail = null;
      _history = [];
      _pending = null;
      _uncertain = false;
      _conflict = false;
    });
    try {
      final detail = await widget.repository.note(row.id, _learner!, _mode);
      if (!_allowed || epoch != _epoch) return;
      if (detail.sessionYearId != _year ||
          (!_clinical && detail.subjectId != _subject)) {
        throw StateError('scope');
      }
      setState(() {
        _detail = detail;
        _body.text = detail.body;
        _busy = false;
      });
    } catch (error) {
      if (_allowed && epoch == _epoch) {
        setState(() {
          _withdraw(error);
          _error = _message(error);
          _busy = false;
        });
      }
    }
  }

  Future<void> _save({bool archive = false}) async {
    if (!_allowed ||
        !_editable ||
        _learner == null ||
        _body.text.trim().isEmpty ||
        _busy ||
        _conflict) {
      return;
    }
    final epoch = _epoch;
    final existing = _detail;
    _writeId ??= const Uuid().v4();
    _pending ??= existing == null
        ? {
            'operation_id': _writeId,
            'session_year_id': _year,
            'subject_id': _subject,
            'visibility': _mode,
            'body': _body.text,
          }
        : {
            'operation_id': _writeId,
            'expected_revision': existing.revision,
            'body': _body.text,
            'archive': archive,
          };
    setState(() {
      _busy = true;
      _error = '';
    });
    try {
      final result = existing == null
          ? await widget.repository.create(
              _learner!,
              LearnerNoteCreate.fromJson(_pending!),
            )
          : await widget.repository.update(
              existing.id,
              LearnerNoteUpdate.fromJson(_pending!),
            );
      if (!_allowed || epoch != _epoch) return;
      setState(() {
        _detail = result;
        _body.text = result.body;
        _writeId = null;
        _pending = null;
        _uncertain = false;
        _busy = false;
      });
      final rows = await widget.repository.notes(
        _learner!,
        _year!,
        _mode,
        subject: _subject,
      );
      if (_allowed && epoch == _epoch) setState(() => _notes = rows.notes);
    } catch (error) {
      if (!_allowed || epoch != _epoch) return;
      final conflict = error is ApiException && error.statusCode == 409;
      final known =
          error is ApiException &&
          error.statusCode != null &&
          error.statusCode! >= 400 &&
          error.statusCode! < 500;
      setState(() {
        _busy = false;
        _withdraw(error);
        _error = _message(error);
        _uncertain = !known;
        _conflict = conflict;
        if (known && !conflict) {
          _pending = null;
          _writeId = null;
        }
      });
    }
  }

  Future<void> _reviewConflict() async {
    if (_detail == null || !_allowed) return;
    final epoch = _epoch;
    try {
      final current = await widget.repository.note(
        _detail!.id,
        _learner!,
        _mode,
      );
      if (!_allowed || epoch != _epoch) return;
      setState(() => _conflictCurrent = current);
    } catch (error) {
      if (_allowed && epoch == _epoch) {
        setState(() {
          _withdraw(error);
          _error = _message(error);
        });
      }
    }
  }

  Future<void> _loadHistory() async {
    final epoch = _epoch, note = _detail?.id;
    if (note == null) return;
    try {
      final rows = await widget.repository.history(note);
      if (_allowed && epoch == _epoch && _detail?.id == note) {
        setState(() => _history = rows.revisions);
      }
    } catch (error) {
      if (_allowed && epoch == _epoch) {
        setState(() {
          _withdraw(error);
          _error = _message(error);
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    if (!_allowed) {
      return ScreenScaffold(
        title: academicText(context, 'Progress and notes'),
        body: Center(
          child: AcademicText(
            'Reopen progress and notes to check your access.',
          ),
        ),
      );
    }
    final config = _config;
    return ScreenScaffold(
      title: academicText(context, 'Progress and notes'),
      body: Center(
        child: ConstrainedBox(
          constraints: const BoxConstraints(maxWidth: 960),
          child: ListView(
            padding: const EdgeInsets.all(16),
            children: [
              if (_busy) const LinearProgressIndicator(),
              if (_error.isNotEmpty)
                Padding(
                  padding: const EdgeInsets.symmetric(vertical: 12),
                  child: AcademicText(_error),
                ),
              if (config == null && !_busy)
                FilledButton(onPressed: _load, child: AcademicText('Retry')),
              if (config != null) ...[
                Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: [
                    if (config.groups.isNotEmpty)
                      ChoiceChip(
                        label: AcademicText('Teaching notes'),
                        selected: !_clinical && !_assigning,
                        onSelected: _busy
                            ? null
                            : (_) {
                                setState(() {
                                  _mode = 'staff';
                                  _subject = null;
                                  _page = 1;
                                });
                                _roster();
                              },
                      ),
                    if (config.canReadCaseload)
                      ChoiceChip(
                        label: AcademicText('Restricted counselling notes'),
                        selected: _clinical,
                        onSelected: _busy
                            ? null
                            : (_) {
                                setState(() {
                                  _mode = 'counselling';
                                  _subject = null;
                                  _page = 1;
                                });
                                _roster();
                              },
                      ),
                    if (config.canAssignCounselling)
                      ChoiceChip(
                        label: AcademicText('Caseload assignments'),
                        selected: _assigning,
                        onSelected: _busy
                            ? null
                            : (_) {
                                setState(() {
                                  _mode = 'assignments';
                                  _subject = null;
                                  _page = 1;
                                });
                                _roster();
                              },
                      ),
                  ],
                ),
                const SizedBox(height: 12),
                if (_clinical)
                  AcademicText(
                    'Restricted notes are available only to explicitly assigned professionals. They do not appear in family progress or tutor context.',
                  ),
                if (!_clinical && !_assigning && config.groups.isNotEmpty)
                  DropdownButtonFormField<int>(
                    initialValue: _group,
                    isExpanded: true,
                    decoration: InputDecoration(
                      labelText: academicText(context, 'Teaching group'),
                    ),
                    items: config.groups
                        .map(
                          (g) => DropdownMenuItem(
                            value: g.id,
                            child: Text(
                              '${g.name} · ${g.subjectName}',
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                        )
                        .toList(),
                    onChanged: _busy
                        ? null
                        : (id) {
                            setState(() {
                              _group = id;
                              _term = _groupTerms.firstOrNull?.id;
                              _band = null;
                              _bandEpoch++;
                              _subject = null;
                              _page = 1;
                            });
                            _roster();
                          },
                  ),
                const SizedBox(height: 12),
                DropdownButtonFormField<int>(
                  key: ValueKey('learner-$_mode-$_group-$_page'),
                  initialValue: _learner,
                  isExpanded: true,
                  decoration: InputDecoration(
                    labelText: academicText(context, 'Learner'),
                  ),
                  items: _learners
                      .map(
                        (l) => DropdownMenuItem(
                          value: l.learnerId,
                          child: Text(
                            l.displayName,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                      )
                      .toList(),
                  onChanged: _busy
                      ? null
                      : (id) {
                          setState(() {
                            _learner = id;
                            _clear();
                          });
                          _list();
                        },
                ),
                if (_lastPage > 1)
                  Row(
                    children: [
                      IconButton(
                        tooltip: academicText(context, 'Previous page'),
                        onPressed: _busy || _page <= 1
                            ? null
                            : () {
                                _page--;
                                _roster();
                              },
                        icon: const Icon(Icons.chevron_left),
                      ),
                      Text('$_page / $_lastPage'),
                      IconButton(
                        tooltip: academicText(context, 'Next page'),
                        onPressed: _busy || _page >= _lastPage
                            ? null
                            : () {
                                _page++;
                                _roster();
                              },
                        icon: const Icon(Icons.chevron_right),
                      ),
                    ],
                  ),
                if (_learner != null && !_assigning) ...[
                  const SizedBox(height: 12),
                  DropdownButtonFormField<int>(
                    initialValue: _year,
                    isExpanded: true,
                    decoration: InputDecoration(
                      labelText: academicText(context, 'School year'),
                    ),
                    items: config.years
                        .map(
                          (y) => DropdownMenuItem(
                            value: y.id,
                            child: Text(y.name),
                          ),
                        )
                        .toList(),
                    onChanged: _busy
                        ? null
                        : (id) {
                            setState(() => _year = id);
                            _list();
                          },
                  ),
                  if (!_clinical) ...[
                    if (_year == config.sessionYearId && _groupTerms.isNotEmpty)
                      DropdownButtonFormField<int>(
                        key: ValueKey('progress-term-$_group-$_term'),
                        initialValue: _term,
                        isExpanded: true,
                        decoration: InputDecoration(
                          labelText: academicText(context, 'Progress term'),
                        ),
                        items: _groupTerms
                            .map(
                              (t) => DropdownMenuItem(
                                value: t.id,
                                child: Text(t.name),
                              ),
                            )
                            .toList(),
                        onChanged: _busy
                            ? null
                            : (id) {
                                setState(() {
                                  _term = id;
                                  _band = null;
                                });
                                _loadBand(_epoch);
                              },
                      ),
                    if (_band != null)
                      Card(
                        child: Padding(
                          padding: const EdgeInsets.all(12),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              AcademicText('Current progress band'),
                              AcademicText(switch (_band!.band.value) {
                                'high' => 'Strong progress',
                                'secure' => 'On track',
                                'support' => 'Extra practice',
                                _ => 'Insufficient evidence',
                              }),
                              if (_band!.percentageBp != null)
                                Text(
                                  '${(_band!.percentageBp! / 100).toStringAsFixed(2)}%',
                                ),
                              Text(
                                '${academicText(context, 'Evidence coverage')}: ${(_band!.coverageBp / 100).toStringAsFixed(2)}%',
                              ),
                              AcademicText(
                                'This staff view may include unreleased work. Family views use released evidence only.',
                              ),
                            ],
                          ),
                        ),
                      ),
                    Wrap(
                      spacing: 8,
                      children: ['staff', 'tutor']
                          .map(
                            (v) => ChoiceChip(
                              label: AcademicText(
                                v == 'staff' ? 'Staff only' : 'Tutor context',
                              ),
                              selected: _mode == v,
                              onSelected: _busy
                                  ? null
                                  : (_) {
                                      setState(() => _mode = v);
                                      _list();
                                    },
                            ),
                          )
                          .toList(),
                    ),
                    SwitchListTile(
                      title: AcademicText('Subject-specific note'),
                      value: _subject != null,
                      onChanged: _busy
                          ? null
                          : (value) {
                              setState(
                                () => _subject = value
                                    ? config.groups
                                          .firstWhere((g) => g.id == _group)
                                          .subjectId
                                    : null,
                              );
                              _list();
                            },
                    ),
                  ],
                  if (!_busy && _notes.isEmpty)
                    AcademicText('No notes for this selection.'),
                  ..._notes.map(
                    (n) => Card(
                      child: ListTile(
                        title: Text(n.authorName),
                        subtitle: Text(
                          '${n.updatedAt.toLocal()}${n.archived ? ' · ${academicText(context, 'Archived')}' : ''}',
                        ),
                        trailing: const Icon(Icons.chevron_right),
                        onTap: _busy ? null : () => _open(n),
                      ),
                    ),
                  ),
                  if (_after > 0)
                    TextButton(
                      onPressed: _busy ? null : () => _list(more: true),
                      child: AcademicText('More saved notes'),
                    ),
                  if (_detail != null || _editable) ...[
                    if (_detail != null && _editable)
                      TextButton(
                        onPressed: _busy
                            ? null
                            : () => setState(() {
                                _detail = null;
                                _body.clear();
                                _history = [];
                                _pending = null;
                                _writeId = null;
                                _conflict = false;
                                _uncertain = false;
                              }),
                        child: AcademicText('New note'),
                      ),
                    TextField(
                      controller: _body,
                      readOnly: !_editable || _busy || _uncertain,
                      minLines: 5,
                      maxLines: 14,
                      maxLength: 20000,
                      autocorrect: false,
                      enableSuggestions: false,
                      enableIMEPersonalizedLearning: false,
                      decoration: InputDecoration(
                        labelText: academicText(context, 'Note'),
                      ),
                      onChanged: (_) {
                        _pending = null;
                        _writeId = null;
                      },
                    ),
                    if (_uncertain)
                      AcademicText(
                        'The save outcome is unknown. Retry the same save before changing this draft.',
                      ),
                    if (_conflictCurrent != null)
                      Card(
                        child: Padding(
                          padding: const EdgeInsets.all(12),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              AcademicText('Current saved note'),
                              Text(_conflictCurrent!.body),
                              FilledButton(
                                onPressed: () {
                                  setState(() {
                                    _detail = _conflictCurrent;
                                    _conflictCurrent = null;
                                    _conflict = false;
                                    _pending = null;
                                    _writeId = null;
                                    _error = '';
                                  });
                                },
                                child: AcademicText(
                                  'Keep my draft and use this revision',
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                    Wrap(
                      spacing: 8,
                      children: [
                        if (_editable && !_conflict)
                          FilledButton(
                            onPressed: _busy ? null : _save,
                            child: AcademicText(
                              _uncertain ? 'Retry same save' : 'Save note',
                            ),
                          ),
                        if (_conflict)
                          FilledButton(
                            onPressed: _busy ? null : _reviewConflict,
                            child: AcademicText('Review the current note'),
                          ),
                        if (_detail != null)
                          TextButton(
                            onPressed: _busy ? null : _loadHistory,
                            child: AcademicText('Revision history'),
                          ),
                        if (_detail != null &&
                            _editable &&
                            !_uncertain &&
                            !_conflict)
                          TextButton(
                            onPressed: _busy
                                ? null
                                : () => _save(archive: true),
                            child: AcademicText('Archive note'),
                          ),
                      ],
                    ),
                    ..._history.map(
                      (r) => Card(
                        child: Padding(
                          padding: const EdgeInsets.all(12),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                '${r.revision} · ${r.authorName} · ${r.createdAt.toLocal()}',
                              ),
                              const SizedBox(height: 8),
                              Text(r.body),
                            ],
                          ),
                        ),
                      ),
                    ),
                  ],
                ],
                if (_assigning && _learner != null)
                  _Assignments(
                    repository: widget.repository,
                    learner: _learner!,
                    staff: config.restrictedStaff,
                    canRead: () => _allowed,
                    key: ValueKey('assign-$_learner'),
                  ),
              ],
            ],
          ),
        ),
      ),
    );
  }
}

class _Assignments extends StatefulWidget {
  const _Assignments({
    required this.repository,
    required this.learner,
    required this.staff,
    required this.canRead,
    super.key,
  });
  final ProgressRepository repository;
  final int learner;
  final List<ProgressOption> staff;
  final bool Function() canRead;
  @override
  State<_Assignments> createState() => _AssignmentsState();
}

class _AssignmentsState extends State<_Assignments> {
  List<LearnerNoteAssignment> _rows = [];
  int? _staff;
  DateTime? _from, _to;
  bool _busy = true;
  String _error = '';
  Future<void> Function()? _retry;
  bool get _active => mounted && widget.canRead() && widget.repository.isActive;
  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    try {
      final r = await widget.repository.assignments(widget.learner);
      if (_active) {
        setState(() {
          _rows = r.assignments;
          _busy = false;
        });
      }
    } catch (_) {
      if (_active) {
        setState(() {
          _error = 'Unable to load caseload assignments.';
          _busy = false;
        });
      }
    }
  }

  String _date(DateTime d) =>
      '${d.year.toString().padLeft(4, '0')}-${d.month.toString().padLeft(2, '0')}-${d.day.toString().padLeft(2, '0')}';
  Future<void> _write(Future<void> Function() action) async {
    if (!_active || _busy) return;
    _retry ??= action;
    setState(() {
      _busy = true;
      _error = '';
    });
    try {
      await _retry!();
      if (!_active) return;
      _retry = null;
      await _load();
    } catch (error) {
      if (!_active) return;
      final known =
          error is ApiException &&
          error.statusCode != null &&
          error.statusCode! >= 400 &&
          error.statusCode! < 500;
      setState(() {
        _error = known
            ? 'Assignment changed or access was withdrawn. Reload before trying again.'
            : 'Save outcome unknown. Retry the same assignment change.';
        _busy = false;
        if (known) _retry = null;
      });
    }
  }

  @override
  Widget build(BuildContext context) => Column(
    crossAxisAlignment: CrossAxisAlignment.start,
    children: [
      AcademicText(
        'Assignment administration exposes dates and staff names, never counselling text.',
      ),
      if (_busy) const LinearProgressIndicator(),
      if (_error.isNotEmpty) AcademicText(_error),
      ..._rows.map(
        (r) => Card(
          child: ListTile(
            title: Text(r.staffName),
            subtitle: Text(
              '${r.startsOn} – ${r.endsOn}${r.revoked == true ? ' · ${academicText(context, 'Revoked')}' : ''}',
            ),
            trailing: r.revoked == true
                ? null
                : IconButton(
                    tooltip: academicText(context, 'End access now'),
                    icon: const Icon(Icons.person_remove_outlined),
                    onPressed: _busy || _retry != null
                        ? null
                        : () {
                            final input = LearnerNoteAssignmentRevoke(
                              operationId: const Uuid().v4(),
                              expectedRevision: r.revision,
                            );
                            _write(() async {
                              await widget.repository.revoke(
                                widget.learner,
                                r.staffUserId,
                                input,
                              );
                            });
                          },
                  ),
          ),
        ),
      ),
      DropdownButtonFormField<int>(
        initialValue: _staff,
        isExpanded: true,
        decoration: InputDecoration(
          labelText: academicText(context, 'Assigned professional'),
        ),
        items: widget.staff
            .map((s) => DropdownMenuItem(value: s.id, child: Text(s.name)))
            .toList(),
        onChanged: _busy || _retry != null
            ? null
            : (id) => setState(() => _staff = id),
      ),
      Wrap(
        spacing: 8,
        children: [
          for (final start in [true, false])
            TextButton(
              onPressed: _busy || _retry != null
                  ? null
                  : () async {
                      final d = await showDatePicker(
                        context: context,
                        initialDate: DateTime.now(),
                        firstDate: DateTime(2000),
                        lastDate: DateTime(2200),
                      );
                      if (_active && d != null) {
                        setState(() {
                          if (start) {
                            _from = d;
                          } else {
                            _to = d;
                          }
                        });
                      }
                    },
              child: Text(
                (start ? _from : _to) == null
                    ? academicText(context, start ? 'Starts on' : 'Ends on')
                    : _date((start ? _from : _to)!),
              ),
            ),
        ],
      ),
      FilledButton(
        onPressed: _busy
            ? null
            : _retry != null
            ? () => _write(_retry!)
            : _staff == null ||
                  _from == null ||
                  _to == null ||
                  _to!.isBefore(_from!)
            ? null
            : () {
                final existing = _rows
                    .where((r) => r.staffUserId == _staff)
                    .firstOrNull;
                final staff = _staff!;
                final input = LearnerNoteAssignmentWrite(
                  operationId: const Uuid().v4(),
                  expectedRevision: existing?.revision ?? 0,
                  startsOn: _date(_from!),
                  endsOn: _date(_to!),
                );
                _write(() async {
                  await widget.repository.assign(widget.learner, staff, input);
                });
              },
        child: AcademicText(
          _retry != null ? 'Retry same change' : 'Save assignment',
        ),
      ),
      if (!_busy && _retry == null)
        TextButton(onPressed: _load, child: AcademicText('Reload assignments')),
    ],
  );
}
