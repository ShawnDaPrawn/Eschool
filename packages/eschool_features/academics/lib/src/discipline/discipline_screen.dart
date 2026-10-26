import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:uuid/uuid.dart';

import '../localization/academic_text.dart';
import 'discipline_repository.dart';

/// Staff-only discipline, with school-defined sanctions and explicit human decisions.
class SchoolDisciplineScreen extends StatefulWidget {
  const SchoolDisciplineScreen({
    required this.repository,
    required this.canRead,
    this.initialRecord,
    super.key,
  });
  final DisciplineRepository repository;
  final bool Function() canRead;
  final int? initialRecord;
  @override
  State<SchoolDisciplineScreen> createState() => _SchoolDisciplineScreenState();
}

class _SchoolDisciplineScreenState extends State<SchoolDisciplineScreen>
    with WidgetsBindingObserver {
  final _scroll = ScrollController();
  final _cancelReason = TextEditingController();
  int? _selectedEntry;
  final _description = TextEditingController(),
      _note = TextEditingController(),
      _reason = TextEditingController(),
      _familyReason = TextEditingController();
  final _date = TextEditingController(), _search = TextEditingController();
  DisciplineConfiguration? _config;
  DisciplineRecordQueue? _records;
  DisciplineLearnerChoices? _people;
  DisciplineLearnerChoice? _learner;
  DisciplineRecordDetail? _record;
  DisciplineRecordHistory? _history;
  DisciplineDetentions? _sessions;
  DisciplineDetentionDetail? _session;
  DisciplineIntent? _intent;
  String _mode = 'records', _error = '', _decision = 'confirm';
  String? _infraction, _sanction;
  int? _year;
  int _epoch = 0, _page = 1, _peoplePage = 1;
  bool _busy = true,
      _dead = false,
      _suspended = false,
      _unknown = false,
      _conflict = false,
      _compared = false;
  bool get _active =>
      mounted &&
      !_dead &&
      !_suspended &&
      widget.canRead() &&
      widget.repository.isActive;
  bool get _writable =>
      _active && _config?.writable == true && _year == _config?.sessionYearId;
  bool _current(int epoch) => _active && epoch == _epoch;
  String l(String s) => academicText(context, s);
  String _status(Object row) {
    final json = row is DisciplineRecordDetail
        ? row.toJson()
        : row is DisciplineRecordMetadata
        ? row.toJson()
        : <String, dynamic>{};
    return switch (json['status']) {
      'pending_review' => l('Awaiting grade review'),
      'confirmed' => l('Confirmed'),
      'hearing' => l('Principal hearing'),
      'overturned' => l('Overturned'),
      _ => l('Unknown status'),
    };
  }

  bool get _draft => [
    _description,
    _note,
    _reason,
    _familyReason,
    _cancelReason,
  ].any((c) => c.text.isNotEmpty);
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _boot();
  }

  @override
  void dispose() {
    _scroll.dispose();
    _cancelReason.dispose();
    WidgetsBinding.instance.removeObserver(this);
    for (final c in [
      _description,
      _note,
      _reason,
      _familyReason,
      _date,
      _search,
    ]) {
      c.dispose();
    }
    super.dispose();
  }

  void _clear() {
    _selectedEntry = null;
    _cancelReason.clear();
    _record = null;
    _history = null;
    _session = null;
    _learner = null;
    _intent = null;
    _unknown = false;
    _conflict = false;
    _compared = false;
    _decision = 'confirm';
    _sanction = null;
    for (final c in [_description, _note, _reason, _familyReason]) {
      c.clear();
    }
  }

  void _erase() {
    _clear();
    _config = null;
    _records = null;
    _people = null;
    _sessions = null;
    _search.clear();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (!mounted) return;
    if (state != AppLifecycleState.resumed) {
      setState(() {
        _suspended = true;
        _epoch++;
        _erase();
        _busy = false;
      });
    } else if (_suspended && !_dead) {
      _suspended = false;
      _boot();
    }
  }

  void _fail(Object error, int epoch) {
    if (!mounted || epoch != _epoch) return;
    final denied =
        error is ApiException && [401, 403, 404].contains(error.statusCode);
    setState(() {
      if (denied || !widget.canRead() || !widget.repository.isActive) {
        _erase();
        _dead = true;
      }
      _busy = false;
      _error = denied
          ? 'Reopen school discipline to check your access.'
          : 'School discipline could not be loaded. Try again.';
    });
  }

  Future<void> _boot() async {
    if (!_active) return;
    final epoch = ++_epoch;
    setState(() {
      _erase();
      _busy = true;
      _error = '';
    });
    try {
      final config = await widget.repository.configuration();
      if (!_current(epoch)) return;
      _config = config;
      _year = config.sessionYearId;
      _date.text = config.today;
      _infraction = config.framework?.definition.infractions.firstOrNull?.code;
      await _load(epoch);
      if (_current(epoch) && widget.initialRecord != null) {
        await _open(widget.initialRecord!);
      }
    } catch (e) {
      _fail(e, epoch);
    }
  }

  Future<void> _load(int epoch) async {
    if (!_current(epoch) || _year == null) return;
    try {
      if (_mode == 'detentions') {
        final rows = await widget.repository.detentions(_year!, page: _page);
        if (!_current(epoch)) return;
        setState(() {
          _sessions = rows;
          _busy = false;
        });
      } else {
        final rows = await widget.repository.records(_year!, page: _page);
        final people = _year == _config?.sessionYearId
            ? await widget.repository.learners(
                _year!,
                page: _peoplePage,
                search: _search.text,
              )
            : null;
        if (!_current(epoch)) return;
        setState(() {
          _records = rows;
          _people = people;
          _busy = false;
        });
      }
    } catch (e) {
      _fail(e, epoch);
    }
  }

  Future<bool> _discard() async {
    if (_unknown) return false;
    if (!_draft) return true;
    return await showDialog<bool>(
          context: context,
          builder: (context) => AlertDialog(
            title: Text(l('Discard this unsaved discipline draft?')),
            content: Text(l('Private drafts are not saved on this device.')),
            actions: [
              TextButton(
                onPressed: () => Navigator.pop(context, false),
                child: Text(l('Keep editing')),
              ),
              FilledButton(
                onPressed: () => Navigator.pop(context, true),
                child: Text(l('Discard draft')),
              ),
            ],
          ),
        ) ??
        false;
  }

  Future<void> _change({String? mode, int? year, int? page}) async {
    if (!await _discard() || !_active) return;
    final epoch = ++_epoch;
    setState(() {
      _clear();
      _mode = mode ?? _mode;
      _year = year ?? _year;
      _page = page ?? 1;
      _busy = true;
      _error = '';
    });
    await _load(epoch);
  }

  Future<void> _open(int id, {bool detention = false}) async {
    if (!await _discard() || !_active) return;
    final epoch = ++_epoch;
    setState(() {
      _clear();
      _busy = true;
      _error = '';
    });
    try {
      if (detention) {
        final row = await widget.repository.detention(id);
        if (!_current(epoch)) return;
        setState(() {
          _session = row;
          _busy = false;
        });
      } else {
        final row = await widget.repository.record(id);
        final history = await widget.repository.history(id);
        final sessions = await widget.repository.detentions(row.sessionYearId);
        if (!_current(epoch)) return;
        setState(() {
          _record = row;
          _history = history;
          _sessions = sessions;
          _busy = false;
        });
      }
    } catch (e) {
      _fail(e, epoch);
    }
  }

  void _showMessage() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted && _scroll.hasClients) {
        _scroll.animateTo(
          0,
          duration: const Duration(milliseconds: 180),
          curve: Curves.easeOut,
        );
      }
    });
  }

  Future<void> _send(DisciplineIntent intent) async {
    if (!_writable || _busy || (_conflict && !_compared)) return;
    final epoch = _epoch;
    setState(() {
      _busy = true;
      _intent = intent;
      _error = '';
    });
    // Only an unknown write outcome retains an exact retry. A later read failure
    // never converts an already confirmed write back into an uncertain mutation.
    try {
      await widget.repository.write(intent);
    } catch (e) {
      if (!_current(epoch)) return;
      if (e is ApiException && e.statusCode == 0) {
        setState(() {
          _busy = false;
          _unknown = true;
          _error =
              'The save outcome is unknown. Retry the exact same operation before editing.';
        });
        _showMessage();
        return;
      }
      if (e is ApiException && e.statusCode == 409) {
        try {
          final current = _record == null
              ? null
              : await widget.repository.record(_record!.id);
          if (!_current(epoch)) return;
          final session = _session == null
              ? null
              : await widget.repository.detention(_session!.id);
          if (!_current(epoch)) return;
          setState(() {
            _record = current;
            _session = session;
            _busy = false;
            _intent = null;
            _unknown = false;
            _conflict = true;
            _compared = false;
            _error =
                'The saved entry changed. Compare your draft with the current revision before saving.';
          });
          _showMessage();
        } catch (error) {
          _fail(error, epoch);
        }
        return;
      }
      if (e is ApiException && [422, 423].contains(e.statusCode)) {
        setState(() {
          _busy = false;
          _intent = null;
          _unknown = false;
          _error =
              'Review your draft and the school rules. No new change was accepted.';
        });
        _showMessage();
        return;
      }
      _fail(e, epoch);
      return;
    }
    if (!_current(epoch)) return;
    final id = _record?.id, sessionId = _session?.id;
    _clear();
    try {
      final record = id == null ? null : await widget.repository.record(id);
      if (!_current(epoch)) return;
      final history = id == null ? null : await widget.repository.history(id);
      if (!_current(epoch)) return;
      final session = sessionId == null
          ? null
          : await widget.repository.detention(sessionId);
      if (!_current(epoch)) return;
      setState(() {
        _record = record;
        _history = history;
        _session = session;
        _error = 'Saved. Current records are reloaded.';
      });
      await _load(epoch);
      if (_current(epoch)) _showMessage();
    } catch (e) {
      _fail(e, epoch);
    }
  }

  Map<String, dynamic> _body() => {
    'operation_id': const Uuid().v4(),
    'session_year_id': _year,
  };
  Widget _field(
    TextEditingController controller,
    String label, {
    int lines = 1,
    int limit = 1000,
  }) => Padding(
    padding: const EdgeInsets.symmetric(vertical: 8),
    child: TextField(
      controller: controller,
      enabled: _writable && !_busy && !_unknown,
      maxLines: lines,
      maxLength: limit,
      onChanged: (_) => setState(() {}),
      decoration: InputDecoration(
        labelText: l(label),
        border: const OutlineInputBorder(),
      ),
    ),
  );
  Widget _button(String label, VoidCallback tap, {bool enabled = true}) =>
      Padding(
        padding: const EdgeInsets.symmetric(vertical: 6),
        child: FilledButton(
          onPressed:
              _writable &&
                  !_busy &&
                  !_unknown &&
                  (!_conflict || _compared) &&
                  enabled
              ? tap
              : null,
          child: Text(l(label)),
        ),
      );
  Widget _card(List<Widget> children) => Card(
    child: Padding(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: children,
      ),
    ),
  );
  Widget _pages(int page, int last, {bool people = false}) => Wrap(
    spacing: 12,
    children: [
      if (page > 1)
        TextButton(
          onPressed: _busy || _unknown
              ? null
              : () => people ? _findPeople(page - 1) : _change(page: page - 1),
          child: Text(l('Previous')),
        ),
      Text('$page / $last'),
      if (page < last)
        TextButton(
          onPressed: _busy || _unknown
              ? null
              : () => people ? _findPeople(page + 1) : _change(page: page + 1),
          child: Text(l('Next')),
        ),
    ],
  );
  Future<void> _findPeople(int page) async {
    if (!_active || _year == null || _unknown) return;
    final epoch = ++_epoch;
    setState(() {
      _busy = true;
    });
    try {
      final rows = await widget.repository.learners(
        _year!,
        page: page,
        search: _search.text,
      );
      if (!_current(epoch)) return;
      setState(() {
        _people = rows;
        _peoplePage = page;
        _busy = false;
      });
    } catch (e) {
      _fail(e, epoch);
    }
  }

  @override
  Widget build(BuildContext context) {
    final config = _config;
    return PopScope(
      canPop: !_draft && !_unknown,
      onPopInvokedWithResult: (didPop, result) async {
        if (!didPop && await _discard() && context.mounted) {
          Navigator.of(context).pop();
        }
      },
      child: ScreenScaffold(
        title: l('School discipline'),
        body: ListView(
          controller: _scroll,
          padding: const EdgeInsets.all(16),
          children: [
            Text(
              l(
                'Record facts and follow your school framework. A hearing is never an automatic expulsion.',
              ),
            ),
            if (_error.isNotEmpty)
              _card([
                Text(l(_error), semanticsLabel: l(_error)),
                if (_unknown && _intent != null)
                  FilledButton(
                    onPressed: _busy ? null : () => _send(_intent!),
                    child: Text(l('Retry exact operation')),
                  ),
              ]),
            if (_busy) const LinearProgressIndicator(),
            if (config != null) ...[
              if (!_writable)
                Text(
                  l('Discipline is read-only. Saved records remain available.'),
                ),
              DropdownButtonFormField<int>(
                isExpanded: true,
                key: ValueKey('year-$_year'),
                initialValue: _year,
                decoration: InputDecoration(labelText: l('School year')),
                items: config.years
                    .map(
                      (y) => DropdownMenuItem(value: y.id, child: Text(y.name)),
                    )
                    .toList(),
                onChanged: _busy || _unknown ? null : (id) => _change(year: id),
              ),
              Wrap(
                spacing: 8,
                children: [
                  for (final mode in ['records', 'detentions'])
                    ChoiceChip(
                      label: Text(
                        l(
                          mode == 'records'
                              ? 'Discipline records'
                              : 'Detention sessions',
                        ),
                      ),
                      selected: _mode == mode,
                      onSelected: _busy || _unknown
                          ? null
                          : (_) => _change(mode: mode),
                    ),
                ],
              ),
              if (_conflict)
                CheckboxListTile(
                  value: _compared,
                  onChanged: (value) =>
                      setState(() => _compared = value ?? false),
                  title: Text(
                    l('I compared my draft with the current saved revision.'),
                  ),
                ),
              if (_mode == 'records')
                ..._recordWidgets(config)
              else
                ..._detentionWidgets(config),
            ] else if (!_busy && !_dead)
              TextButton(onPressed: _boot, child: Text(l('Retry'))),
          ],
        ),
      ),
    );
  }

  List<Widget> _recordWidgets(DisciplineConfiguration config) {
    final record = _record;
    final canReview =
        record != null &&
        (config.canManage || config.gradeIds.contains(record.gradeLevelId)) &&
        (record.hearingDecision == null || config.canHear);
    if (record != null) {
      return [
        _card([
          Text(
            l('Current discipline entry'),
            style: Theme.of(context).textTheme.titleLarge,
          ),
          Text(
            '${record.occurredOn} · ${_status(record)} · ${l('Revision')} ${record.revision}',
          ),
          Text(record.basis.infractionName),
          SelectableText(record.description),
          Text(l('Private staff note')),
          SelectableText(record.staffNote ?? ''),
          if (record.decisionReason != null)
            SelectableText(record.decisionReason!),
          if (record.familyReason != null) SelectableText(record.familyReason!),
          if (canReview &&
              record.toJson()['status'] != 'overturned' &&
              !(record.toJson()['status'] == 'hearing' && config.canHear)) ...[
            DropdownButtonFormField<String>(
              isExpanded: true,
              key: ValueKey('decision-${record.revision}'),
              initialValue: record.toJson()['status'] == 'pending_review'
                  ? _decision
                  : 'overturn',
              decoration: InputDecoration(labelText: l('Review decision')),
              items: [
                if (record.toJson()['status'] == 'pending_review')
                  DropdownMenuItem(
                    value: 'confirm',
                    child: Text(l('Confirm after review')),
                  ),
                DropdownMenuItem(
                  value: 'overturn',
                  child: Text(l('Overturn with a reason')),
                ),
              ],
              onChanged: _writable && !_unknown && !_busy
                  ? (v) => setState(() => _decision = v ?? 'confirm')
                  : null,
            ),
            _sanctions(record),
            _field(_reason, 'Private review reason', lines: 3),
            _field(_familyReason, 'Reason the family may read', lines: 3),
            _button(
              'Save reasoned review',
              () => _send(
                DisciplineIntent(DisciplineAction.review, {
                  ..._body(),
                  'expected_revision': record.revision,
                  'decision': record.toJson()['status'] == 'pending_review'
                      ? _decision
                      : 'overturn',
                  'reason': _reason.text,
                  'family_reason': _familyReason.text,
                  'sanction_code': _sanction,
                }, record: record.id),
              ),
              enabled: _reason.text.trim().length >= 3,
            ),
          ],
          if (record.toJson()['status'] == 'hearing' && config.canHear) ...[
            Text(l('Principal hearing decision')),
            _field(_reason, 'Principal decision reason', lines: 3),
            _field(
              _familyReason,
              'Principal reason the family may read',
              lines: 3,
            ),
            _sanctions(record),
            _button(
              'Record no further action',
              () => _send(
                DisciplineIntent(DisciplineAction.hearing, {
                  ..._body(),
                  'expected_revision': record.revision,
                  'decision': 'no_action',
                  'reason': _reason.text,
                  'family_reason': _familyReason.text,
                }, record: record.id),
              ),
              enabled: _reason.text.trim().length >= 3,
            ),
            _button(
              'Record defined hearing sanction',
              () => _send(
                DisciplineIntent(DisciplineAction.hearing, {
                  ..._body(),
                  'expected_revision': record.revision,
                  'decision': 'sanction',
                  'reason': _reason.text,
                  'family_reason': _familyReason.text,
                  'sanction_code': _sanction,
                }, record: record.id),
              ),
              enabled: _reason.text.trim().length >= 3 && _sanction != null,
            ),
          ],
          if (canReview &&
              record.toJson()['status'] == 'confirmed' &&
              record.basis.definition.sanctions.any(
                (s) =>
                    s.code == record.sanctionCode &&
                    s.toJson()['kind'] == 'detention',
              )) ...[
            Text(l('Schedule the confirmed detention')),
            for (final session
                in _sessions?.sessions ?? <DisciplineDetention>[])
              if (session.gradeLevelId == record.gradeLevelId &&
                  session.toJson()['status'] == 'planned')
                _button(
                  '${session.onDate} ${session.startsAt}–${session.endsAt}',
                  () => _send(
                    DisciplineIntent(
                      DisciplineAction.enroll,
                      {
                        ..._body(),
                        'record_id': record.id,
                        'expected_revision': session.revision,
                      },
                      record: record.id,
                      detention: session.id,
                    ),
                  ),
                ),
            Text(l('Plan supervised sessions in the school panel.')),
          ],
          Text(l('Immutable entry history')),
          for (final version
              in _history?.versions ?? <DisciplineRecordVersion>[])
            ExpansionTile(
              title: Text('${l('Revision')} ${version.revision}'),
              children: [
                SelectableText(version.record.description),
                SelectableText(version.record.staffNote ?? ''),
                SelectableText(version.record.decisionReason ?? ''),
              ],
            ),
          TextButton(
            onPressed: _busy || _unknown ? null : () => _change(),
            child: Text(l('Back to entries')),
          ),
        ]),
      ];
    }
    return [
      if (_records != null)
        _card([
          Text(l('Retained discipline entries')),
          for (final row in _records!.records)
            ListTile(
              title: Text(row.learnerName ?? l('Learner')),
              subtitle: Text('${row.occurredOn} · ${_status(row)}'),
              trailing: const Icon(Icons.chevron_right),
              onTap: _busy || _unknown ? null : () => _open(row.id),
            ),
          if (_records!.records.isEmpty)
            Text(
              l(
                'No authorized discipline entries are available for this selection.',
              ),
            ),
          _pages(_records!.page, _records!.lastPage),
        ]),
      if (_year == config.sessionYearId && config.framework != null)
        _card([
          Text(l('Record a factual occurrence')),
          TextField(
            controller: _search,
            enabled: !_busy && !_unknown,
            decoration: InputDecoration(labelText: l('Find a learner by name')),
          ),
          TextButton(
            onPressed: _busy || _unknown ? null : () => _findPeople(1),
            child: Text(l('Find learners')),
          ),
          for (final person in _people?.learners ?? <DisciplineLearnerChoice>[])
            ListTile(
              selected: _learner?.learnerId == person.learnerId,
              title: Text(person.name),
              leading: Icon(
                _learner?.learnerId == person.learnerId
                    ? Icons.check_circle
                    : Icons.circle_outlined,
              ),
              onTap: _writable && !_busy && !_unknown
                  ? () => setState(() => _learner = person)
                  : null,
            ),
          if (_people != null)
            _pages(_people!.page, _people!.lastPage, people: true),
          _field(_date, 'Occurrence date', limit: 10),
          DropdownButtonFormField<String>(
            isExpanded: true,
            key: ValueKey('infraction-$_infraction'),
            initialValue: _infraction,
            decoration: InputDecoration(labelText: l('School infraction')),
            items: config.framework!.definition.infractions
                .map(
                  (i) => DropdownMenuItem(value: i.code, child: Text(i.name)),
                )
                .toList(),
            onChanged: _writable && !_busy && !_unknown
                ? (v) => setState(() => _infraction = v)
                : null,
          ),
          _field(
            _description,
            'Factual description the family may read',
            lines: 4,
            limit: 2000,
          ),
          _field(_note, 'Private staff follow-up note', lines: 4, limit: 2000),
          _button(
            'Record occurrence',
            () => _send(
              DisciplineIntent(DisciplineAction.create, {
                ..._body(),
                'occurred_on': _date.text,
                'infraction_code': _infraction,
                'description': _description.text,
                'staff_note': _note.text,
              }, learner: _learner?.learnerId),
            ),
            enabled:
                _learner != null &&
                _description.text.trim().isNotEmpty &&
                _infraction != null,
          ),
        ])
      else
        Text(
          l(
            'Your school must publish its discipline framework before occurrences can be recorded.',
          ),
        ),
    ];
  }

  Widget _sanctions(DisciplineRecordDetail record) =>
      DropdownButtonFormField<String>(
        isExpanded: true,
        key: ValueKey('sanction-${record.id}-${record.revision}'),
        initialValue: _sanction,
        decoration: InputDecoration(labelText: l('Approved sanction')),
        items: [
          DropdownMenuItem(value: null, child: Text(l('No sanction selected'))),
          for (final s in record.basis.definition.sanctions)
            DropdownMenuItem(value: s.code, child: Text(s.name)),
        ],
        onChanged: _writable && !_busy && !_unknown
            ? (v) => setState(() => _sanction = v)
            : null,
      );
  Future<void> _selectEntry(int id) async {
    if (_busy || !_active || !await _discard()) return;
    setState(() {
      _epoch++;
      _selectedEntry = id;
      _reason.clear();
      _cancelReason.clear();
      _intent = null;
      _conflict = false;
      _compared = false;
    });
  }

  List<Widget> _detentionWidgets(DisciplineConfiguration config) {
    final session = _session;
    if (session == null) {
      return [
        _card([
          Text(l('Detention sessions')),
          Text(l('Plan supervised sessions in the school panel.')),
          for (final row in _sessions?.sessions ?? <DisciplineDetention>[])
            ListTile(
              title: Text('${row.onDate} ${row.startsAt}–${row.endsAt}'),
              subtitle: Text(row.timezone),
              onTap: _busy ? null : () => _open(row.id, detention: true),
            ),
          if (_sessions != null) _pages(_sessions!.page, _sessions!.lastPage),
        ]),
      ];
    }
    final head =
        config.canManage || config.gradeIds.contains(session.gradeLevelId);
    return [
      _card([
        Text(l('Supervised detention register')),
        Text(
          '${session.onDate} ${session.startsAt}–${session.endsAt} · ${session.timezone}',
        ),
        Text(session.location),
        Text(
          l(
            'Missing attendance is unknown. Corrections need a grade-head reason.',
          ),
        ),
        for (final entry in session.entries) ...[
          Text(entry.name, style: Theme.of(context).textTheme.titleMedium),
          Text(
            entry.attendance == null
                ? l('Not captured')
                : l(entry.toJson()['attendance'].toString()),
          ),
          if (session.toJson()['status'] == 'planned' &&
              entry.sourceValid &&
              entry.toJson()['status'] == 'active' &&
              (entry.attendance == null || head))
            TextButton(
              onPressed: _writable && !_busy && !_unknown
                  ? () => _selectEntry(entry.id)
                  : null,
              child: Text('${l('Open attendance capture')} · ${entry.name}'),
            ),
          if (_selectedEntry == entry.id &&
              session.toJson()['status'] == 'planned' &&
              entry.sourceValid &&
              entry.toJson()['status'] == 'active' &&
              (entry.attendance == null || head)) ...[
            _field(_reason, 'Capture or correction reason', lines: 2),
            Wrap(
              spacing: 8,
              children: [
                for (final state in ['present', 'absent', 'excused'])
                  _button(
                    state,
                    () => _send(
                      DisciplineIntent(
                        DisciplineAction.attendance,
                        {
                          ..._body(),
                          'expected_revision': entry.revision,
                          'expected_session_revision': session.revision,
                          'attendance': state,
                          'reason': _reason.text,
                        },
                        detention: session.id,
                        entry: entry.id,
                      ),
                    ),
                    enabled: _reason.text.trim().length >= 3,
                  ),
              ],
            ),
          ],
        ],
        if (head && session.toJson()['status'] == 'planned') ...[
          _field(_cancelReason, 'Cancellation reason', lines: 2),
          _button(
            'Cancel retained session',
            () => _send(
              DisciplineIntent(DisciplineAction.cancel, {
                ..._body(),
                'expected_revision': session.revision,
                'reason': _cancelReason.text,
              }, detention: session.id),
            ),
            enabled: _cancelReason.text.trim().length >= 3,
          ),
        ],
        TextButton(
          onPressed: _busy || _unknown ? null : () => _change(),
          child: Text(l('Back to sessions')),
        ),
      ]),
    ];
  }
}
