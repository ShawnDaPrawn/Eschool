import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:uuid/uuid.dart';
import '../localization/academic_text.dart';
import 'risk_repository.dart';

class RiskInterventionsScreen extends StatefulWidget {
  const RiskInterventionsScreen({
    required this.repository,
    required this.canRead,
    super.key,
  });
  final RiskRepository repository;
  final bool Function() canRead;
  @override
  State<RiskInterventionsScreen> createState() =>
      _RiskInterventionsScreenState();
}

class _Pending {
  _Pending(this.kind, this.data, {this.id, this.learner});
  final String kind;
  final Map<String, dynamic> data;
  final int? id, learner;
}

class _RiskInterventionsScreenState extends State<RiskInterventionsScreen>
    with WidgetsBindingObserver {
  final _action = TextEditingController(),
      _outcome = TextEditingController(),
      _reason = TextEditingController();
  final _start = TextEditingController(),
      _review = TextEditingController(),
      _search = TextEditingController();
  RiskConfiguration? _config;
  InterventionQueue? _queue;
  RiskSignals? _signals;
  RiskClassroom? _classroom;
  RiskLearnerChoices? _choices;
  RiskOwnerChoices? _owners;
  RiskLearnerChoice? _learner;
  RiskOwnerChoice? _owner;
  InterventionDetail? _detail, _newer;
  InterventionQueueItem? _handoff;
  InterventionHistory? _history;
  _Pending? _pending;
  int _epoch = 0, _page = 1, _learnerPage = 1, _ownerPage = 1;
  int? _year, _group;
  String _mode = 'queue', _state = 'active', _error = '';
  bool _busy = true,
      _suspended = false,
      _dead = false,
      _uncertain = false,
      _conflict = false,
      _compared = false;
  String l(String value) => academicText(context, value);
  String _today() =>
      _config?.today ?? DateTime.now().toIso8601String().substring(0, 10);
  bool get _allowed =>
      mounted &&
      !_suspended &&
      !_dead &&
      widget.canRead() &&
      widget.repository.isActive;
  bool _current(int epoch) => _allowed && epoch == _epoch;
  bool get _draft =>
      _action.text.isNotEmpty ||
      _outcome.text.isNotEmpty ||
      _reason.text.isNotEmpty;
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _boot();
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    for (final c in [_action, _outcome, _reason, _start, _review, _search]) {
      c.dispose();
    }
    super.dispose();
  }

  void _clearSelection() {
    _detail = null;
    _history = null;
    _handoff = null;
    _newer = null;
    _learner = null;
    _owner = null;
    _owners = null;
    _pending = null;
    _uncertain = false;
    _conflict = false;
    _compared = false;
    _state = 'active';
    _action.clear();
    _outcome.clear();
    _reason.clear();
    _start.text = _today();
    _review.text = _today();
  }

  void _erase() {
    _clearSelection();
    _config = null;
    _queue = null;
    _signals = null;
    _classroom = null;
    _choices = null;
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
          ? 'Reopen staff follow-up to check your current access.'
          : 'The follow-up could not be loaded. Try again.';
    });
  }

  Future<void> _boot() async {
    if (!_allowed) return;
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
      _start.text = _today();
      _review.text = _today();
      _year = config.sessionYearId;
      _page = 1;
      _learnerPage = 1;
      await _load(epoch);
    } catch (e) {
      _fail(e, epoch);
    }
  }

  Future<void> _load(int epoch) async {
    if (!_current(epoch) || _year == null) return;
    try {
      if (_mode == 'signals') {
        final row = await widget.repository.signals(_year!, page: _page);
        if (!_current(epoch)) return;
        setState(() {
          _signals = row;
          _busy = false;
        });
      } else if (_mode == 'classroom') {
        if (_group == null) {
          setState(() => _busy = false);
          return;
        }
        final row = await widget.repository.classroom(_group!);
        if (!_current(epoch)) return;
        setState(() {
          _classroom = row;
          _busy = false;
        });
      } else {
        final rows = await widget.repository.queue(_year!, page: _page);
        final choices = await widget.repository.learners(
          _year!,
          page: _learnerPage,
          search: _search.text,
        );
        if (!_current(epoch)) return;
        setState(() {
          _queue = rows;
          _choices = choices;
          _busy = false;
        });
      }
    } catch (e) {
      _fail(e, epoch);
    }
  }

  Future<bool> _discard() async {
    if (_uncertain) return false;
    if (!_draft) return true;
    return await showDialog<bool>(
          context: context,
          builder: (context) => AlertDialog(
            title: Text(l('Discard this unsaved follow-up?')),
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

  Future<void> _change(String mode, {int? year, int? page, int? group}) async {
    if (!await _discard() || !_allowed) return;
    final epoch = ++_epoch;
    setState(() {
      _mode = mode;
      _year = year ?? _year;
      _group = group ?? _group;
      _page = page ?? 1;
      _learnerPage = 1;
      _clearSelection();
      _queue = null;
      _signals = null;
      _classroom = null;
      _busy = true;
      _error = '';
    });
    await _load(epoch);
  }

  Future<void> _choose(RiskLearnerChoice? learner) async {
    if (learner == null || !await _discard() || !_allowed) return;
    final epoch = ++_epoch;
    setState(() {
      _clearSelection();
      _learner = learner;
      _ownerPage = 1;
      _busy = true;
      _error = '';
    });
    try {
      final rows = await widget.repository.owners(learner.learnerId, _year!);
      if (!_current(epoch)) return;
      setState(() {
        _owners = rows;
        _owner = rows.owners
            .where((r) => r.id == widget.repository.readerId)
            .firstOrNull;
        _busy = false;
      });
    } catch (e) {
      _fail(e, epoch);
    }
  }

  Future<void> _open(InterventionQueueItem row, {bool handoff = false}) async {
    if ((!handoff && !row.canReadBody) || !await _discard() || !_allowed) {
      return;
    }
    final epoch = ++_epoch;
    setState(() {
      _clearSelection();
      _busy = true;
      _error = '';
    });
    try {
      if (handoff) {
        final owners = await widget.repository.owners(
          row.learnerId,
          _year!,
          intervention: row.id,
        );
        if (!_current(epoch)) return;
        setState(() {
          _handoff = row;
          _owners = owners;
          _busy = false;
        });
      } else {
        final detail = await widget.repository.detail(row.id);
        final history = await widget.repository.history(row.id);
        if (!_current(epoch)) return;
        setState(() {
          _detail = detail;
          _history = history;
          _action.text = detail.action;
          _outcome.text = detail.outcome ?? '';
          _start.text = detail.startedOn;
          _review.text = detail.reviewOn;
          _state = detail.toJson()['state'] as String;
          _busy = false;
        });
      }
    } catch (e) {
      _fail(e, epoch);
    }
  }

  Future<void> _learners(int page) async {
    if (!_allowed) return;
    final epoch = ++_epoch;
    setState(() => _busy = true);
    try {
      final rows = await widget.repository.learners(
        _year!,
        page: page,
        search: _search.text,
      );
      if (!_current(epoch)) return;
      setState(() {
        _choices = rows;
        _learnerPage = page;
        _busy = false;
      });
    } catch (e) {
      _fail(e, epoch);
    }
  }

  Future<void> _pageOwners(int page) async {
    final learner = _handoff?.learnerId ?? _learner?.learnerId;
    if (!_allowed || learner == null) return;
    final epoch = ++_epoch;
    setState(() => _busy = true);
    try {
      final rows = await widget.repository.owners(
        learner,
        _year!,
        intervention: _handoff?.id,
        page: page,
      );
      if (!_current(epoch)) return;
      setState(() {
        _owners = rows;
        _ownerPage = page;
        _busy = false;
      });
    } catch (e) {
      _fail(e, epoch);
    }
  }

  Future<void> _save() async {
    if (!_allowed || _busy) return;
    if (_uncertain) {
      await _dispatch();
      return;
    }
    if (_conflict && !_compared) {
      setState(
        () => _error = 'Compare the current saved action before trying again.',
      );
      return;
    }
    final uuid = const Uuid().v4();
    if (_handoff != null) {
      if (_owner == null || _reason.text.trim().length < 3) {
        setState(() => _error = 'Choose an eligible owner and enter a reason.');
        return;
      }
      _pending = _Pending(
        'transfer',
        Map.unmodifiable({
          'operation_id': uuid,
          'expected_revision': _handoff!.revision,
          'owner_id': _owner!.id,
          'reason': _reason.text.trim(),
        }),
        id: _handoff!.id,
      );
    } else {
      if (_action.text.trim().isEmpty ||
          !RegExp(r'^\d{4}-\d{2}-\d{2}$').hasMatch(_review.text) ||
          (_state == 'completed' && _outcome.text.trim().isEmpty)) {
        setState(
          () => _error =
              'Enter an action, a calendar review date and an outcome for completed care.',
        );
        return;
      }
      if (_detail != null) {
        _pending = _Pending(
          'update',
          Map.unmodifiable({
            'operation_id': uuid,
            'expected_revision': _newer?.revision ?? _detail!.revision,
            'action': _action.text.trim(),
            'review_on': _review.text,
            'state': _state,
            'outcome': _outcome.text.trim().isEmpty
                ? null
                : _outcome.text.trim(),
          }),
          id: _detail!.id,
        );
      } else {
        if (_learner == null ||
            _owner == null ||
            !RegExp(r'^\d{4}-\d{2}-\d{2}$').hasMatch(_start.text)) {
          setState(
            () => _error =
                'Choose a learner, an eligible owner and a calendar start date.',
          );
          return;
        }
        _pending = _Pending(
          'create',
          Map.unmodifiable({
            'operation_id': uuid,
            'session_year_id': _year,
            'owner_id': _owner!.id,
            'owner_authority': _owner!.toJson()['authority'],
            'action': _action.text.trim(),
            'started_on': _start.text,
            'review_on': _review.text,
          }),
          learner: _learner!.learnerId,
        );
      }
    }
    await _dispatch();
  }

  Future<void> _dispatch() async {
    final p = _pending;
    if (!_allowed || p == null) return;
    final epoch = ++_epoch;
    setState(() {
      _busy = true;
      _error = '';
    });
    try {
      switch (p.kind) {
        case 'create':
          await widget.repository.create(
            p.learner!,
            InterventionCreate.fromJson(p.data),
          );
        case 'update':
          await widget.repository.update(
            p.id!,
            InterventionUpdate.fromJson(p.data),
          );
        case 'transfer':
          await widget.repository.transfer(
            p.id!,
            InterventionOwnerWrite.fromJson(p.data),
          );
      }
      if (!_current(epoch)) return;
      setState(() {
        _clearSelection();
        _busy = true;
      });
      await _load(epoch);
    } catch (e) {
      if (!mounted || epoch != _epoch) return;
      if (e is ApiException && [401, 403, 404].contains(e.statusCode)) {
        _fail(e, epoch);
        return;
      }
      if (e is ApiException && e.statusCode == 409) {
        InterventionDetail? current;
        InterventionQueue? refreshed;
        if (p.kind == 'transfer') {
          try {
            refreshed = await widget.repository.queue(_year!, page: _page);
          } catch (error) {
            _fail(error, epoch);
            return;
          }
        }
        if (p.kind == 'update') {
          try {
            current = await widget.repository.detail(p.id!);
          } catch (error) {
            _fail(error, epoch);
            return;
          }
        }
        if (!_current(epoch)) return;
        setState(() {
          _pending = null;
          _conflict = true;
          _compared = false;
          _newer = current;
          if (refreshed != null) {
            _queue = refreshed;
            _handoff = refreshed.interventions
                .where((r) => r.id == p.id)
                .firstOrNull;
            if (_owner?.id == _handoff?.ownerId) {
              _owner = null;
            }
          }
          _busy = false;
          _error =
              'The saved action changed. Compare the current record before saving a new revision.';
        });
      } else if (e is ApiException &&
          (e.statusCode ?? 0) >= 400 &&
          (e.statusCode ?? 0) < 500) {
        if (!_current(epoch)) return;
        setState(() {
          _pending = null;
          _busy = false;
          _error =
              'The change was refused. Check the dates and current scope before trying again.';
        });
      } else {
        if (!_current(epoch)) return;
        setState(() {
          _uncertain = true;
          _busy = false;
          _error =
              'The result is unknown. Retry the same change or check saved records before starting another.';
        });
      }
    }
  }

  Widget _pages(int page, int last, Future<void> Function(int) callback) =>
      Wrap(
        spacing: 12,
        children: [
          if (page > 1)
            TextButton(
              onPressed: _busy || _uncertain ? null : () => callback(page - 1),
              child: Text(l('Previous')),
            ),
          Text('${l('Page')} $page / $last'),
          if (page < last)
            TextButton(
              onPressed: _busy || _uncertain ? null : () => callback(page + 1),
              child: Text(l('Next')),
            ),
        ],
      );
  Widget _field(
    String label,
    TextEditingController controller, {
    int lines = 1,
    int? max,
  }) => Padding(
    padding: const EdgeInsets.symmetric(vertical: 8),
    child: TextField(
      controller: controller,
      enabled: !_busy && !_uncertain,
      minLines: lines,
      maxLines: lines + 3,
      maxLength: max,
      decoration: InputDecoration(labelText: l(label)),
    ),
  );
  Widget _signal(
    String source,
    int? score,
    String reason,
    String day,
  ) => ListTile(
    contentPadding: EdgeInsets.zero,
    title: Text(
      l(
        {
              'academic': 'Academic',
              'attendance': 'Attendance',
              'discipline': 'Discipline',
              'wellbeing': 'Wellbeing',
            }[source] ??
            'Recorded evidence',
      ),
    ),
    subtitle: Text(
      '${l({'academic_below_threshold': 'Below the school academic threshold', 'recorded_absence_threshold': 'Recorded absence needs follow-up', 'confirmed_discipline_count': 'Confirmed discipline entries', 'no_observed_signal': 'No concern in recorded evidence', 'insufficient_evidence': 'Not enough recorded evidence', 'source_unavailable': 'Source not available'}[reason] ?? 'Review the recorded evidence')} · $day',
    ),
    trailing: Text(
      score == null ? l('Unknown') : '${l('Observed score')}: $score',
    ),
  );
  Widget _ownerPicker() {
    final people = <RiskOwnerChoice>[
      if (_owner != null &&
          !(_owners?.owners.any((p) => p.id == _owner!.id) ?? false))
        _owner!,
      ...?_owners?.owners,
    ];
    return Column(
      children: [
        DropdownButtonFormField<int>(
          key: ValueKey('risk-owner-${_owner?.id}-$_ownerPage'),
          initialValue: _owner?.id,
          isExpanded: true,
          decoration: InputDecoration(labelText: l('Assigned owner')),
          items: [
            for (final p in people)
              if (_handoff == null || p.id != _handoff!.ownerId)
                DropdownMenuItem(value: p.id, child: Text(p.name)),
          ],
          onChanged: _busy || _uncertain
              ? null
              : (id) => setState(
                  () => _owner = people.where((p) => p.id == id).firstOrNull,
                ),
        ),
        if (_owners != null)
          _pages(_owners!.page, _owners!.lastPage, _pageOwners),
      ],
    );
  }

  @override
  Widget build(BuildContext context) {
    if (!_suspended &&
        !_dead &&
        (!widget.canRead() || !widget.repository.isActive)) {
      _dead = true;
      _epoch++;
      _erase();
    }
    if (_dead || _suspended) {
      return ScreenScaffold(
        title: l('Staff follow-up'),
        body: Center(
          child: Text(
            l('Reopen staff follow-up to check your current access.'),
          ),
        ),
      );
    }
    final config = _config;
    return ScreenScaffold(
      title: l('Staff follow-up'),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Text(
            l(
              'Recorded evidence guides a staff follow-up. Missing evidence stays unknown; this is not a diagnosis or a learner label.',
            ),
          ),
          if (_error.isNotEmpty)
            Text(
              l(_error),
              key: const Key('risk-error'),
              style: TextStyle(color: Theme.of(context).colorScheme.error),
            ),
          if (_busy) const LinearProgressIndicator(),
          if (config != null) ...[
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: [
                for (final mode in <String, String>{
                  'queue': 'Review actions',
                  'classroom': 'Class follow-up',
                  if (config.canReadSignals) 'signals': 'Early warning list',
                }.entries)
                  ChoiceChip(
                    label: Text(l(mode.value)),
                    selected: _mode == mode.key,
                    onSelected: _busy || _uncertain
                        ? null
                        : (_) => _change(mode.key),
                  ),
              ],
            ),
            if (_mode != 'classroom')
              DropdownButtonFormField<int>(
                key: ValueKey('risk-year-$_year'),
                initialValue: _year,
                isExpanded: true,
                decoration: InputDecoration(labelText: l('School year')),
                items: [
                  for (final y in config.years)
                    DropdownMenuItem(value: y.id, child: Text(y.name)),
                ],
                onChanged: _busy || _uncertain
                    ? null
                    : (year) => _change(_mode, year: year),
              ),
            if (_mode == 'classroom') ...[
              DropdownButtonFormField<int>(
                key: ValueKey('risk-group-$_group'),
                initialValue: _group,
                isExpanded: true,
                decoration: InputDecoration(labelText: l('Teaching group')),
                items: [
                  for (final g in config.groups)
                    DropdownMenuItem(
                      value: g.id,
                      child: Text('${g.name} · ${g.subjectName}'),
                    ),
                ],
                onChanged: _busy
                    ? null
                    : (group) => _change('classroom', group: group),
              ),
              for (final p in _classroom?.learners ?? <RiskClassroomLearner>[])
                Card(
                  child: Padding(
                    padding: const EdgeInsets.all(12),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          p.name,
                          style: Theme.of(context).textTheme.titleMedium,
                        ),
                        for (final s in p.signals)
                          _signal(
                            s.toJson()['source'] as String,
                            s.score,
                            s.reasonCode,
                            s.asOf,
                          ),
                      ],
                    ),
                  ),
                ),
            ] else if (_mode == 'signals') ...[
              for (final p in _signals?.learners ?? <RiskSignalLearner>[])
                Card(
                  child: Padding(
                    padding: const EdgeInsets.all(12),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          p.name,
                          style: Theme.of(context).textTheme.titleMedium,
                        ),
                        for (final s in p.signals)
                          _signal(
                            s.toJson()['source'] as String,
                            s.score,
                            s.reasonCode,
                            s.asOf,
                          ),
                      ],
                    ),
                  ),
                ),
              if (_signals != null)
                _pages(
                  _signals!.page,
                  _signals!.lastPage,
                  (page) => _change('signals', page: page),
                ),
              if (!_busy && (_signals?.learners.isEmpty ?? true))
                Text(
                  l(
                    'No follow-up evidence is available for this selection yet.',
                  ),
                ),
            ] else ...[
              for (final row
                  in _queue?.interventions ?? <InterventionQueueItem>[])
                Card(
                  child: Padding(
                    padding: const EdgeInsets.all(12),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          row.learnerName ?? l('Assigned learner'),
                          style: Theme.of(context).textTheme.titleMedium,
                        ),
                        Text(
                          '${row.ownerName ?? l('Assigned staff member')} · ${l('Review date')}: ${row.reviewOn} · ${l(row.toJson()['state'] as String)}',
                        ),
                        if (row.canReadBody)
                          TextButton(
                            onPressed: _busy || _uncertain
                                ? null
                                : () => _open(row),
                            child: Text(l('Open your intervention')),
                          )
                        else
                          Text(
                            l(
                              'Action text is restricted to its current authorized owner.',
                            ),
                          ),
                        if (config.canManage ||
                            config.gradeIds.contains(row.gradeLevelId))
                          TextButton(
                            onPressed: _busy || _uncertain
                                ? null
                                : () => _open(row, handoff: true),
                            child: Text(l('Change the assigned owner')),
                          ),
                      ],
                    ),
                  ),
                ),
              if (_queue != null)
                _pages(
                  _queue!.page,
                  _queue!.lastPage,
                  (page) => _change('queue', page: page),
                ),
              if (!_busy && (_queue?.interventions.isEmpty ?? true))
                Text(
                  l(
                    'No owned or assigned intervention metadata is available for this year.',
                  ),
                ),
              if (_detail == null &&
                  _handoff == null &&
                  _year == config.sessionYearId) ...[
                const Divider(),
                Text(
                  l('Plan an owned follow-up'),
                  style: Theme.of(context).textTheme.titleLarge,
                ),
                _field('Find a learner', _search, max: 100),
                TextButton(
                  onPressed: _busy || _uncertain ? null : () => _learners(1),
                  child: Text(l('Search')),
                ),
                DropdownButtonFormField<int>(
                  key: ValueKey(
                    'risk-learner-${_learner?.learnerId}-$_learnerPage',
                  ),
                  initialValue: _learner?.learnerId,
                  isExpanded: true,
                  decoration: InputDecoration(labelText: l('Learner')),
                  items: [
                    for (final p in <RiskLearnerChoice>[
                      if (_learner != null &&
                          !(_choices?.learners.any(
                                (p) => p.learnerId == _learner!.learnerId,
                              ) ??
                              false))
                        _learner!,
                      ...?_choices?.learners,
                    ])
                      DropdownMenuItem(value: p.learnerId, child: Text(p.name)),
                  ],
                  onChanged: _busy || _uncertain
                      ? null
                      : (id) => _choose(
                          _choices?.learners
                                  .where((p) => p.learnerId == id)
                                  .firstOrNull ??
                              (_learner?.learnerId == id ? _learner : null),
                        ),
                ),
                if (_choices != null)
                  _pages(_choices!.page, _choices!.lastPage, _learners),
              ],
              if (_detail != null) ...[
                const Divider(),
                Text(
                  l('Current action'),
                  style: Theme.of(context).textTheme.titleMedium,
                ),
                SelectableText(_detail!.action),
                if (_detail!.outcome != null) SelectableText(_detail!.outcome!),
              ],
              if (_handoff != null) ...[
                const Divider(),
                Text(
                  l('Change the assigned owner'),
                  style: Theme.of(context).textTheme.titleLarge,
                ),
                Text(
                  l(
                    'Assignment administration does not grant intervention body access.',
                  ),
                ),
              ],
              if (_handoff != null || (_learner != null && _detail == null))
                _ownerPicker(),
              if (_newer != null) ...[
                Text(
                  l('A newer saved action'),
                  style: Theme.of(context).textTheme.titleMedium,
                ),
                SelectableText(_newer!.action),
              ],
              if (_conflict)
                CheckboxListTile(
                  value: _compared,
                  onChanged: _busy
                      ? null
                      : (v) => setState(() => _compared = v ?? false),
                  title: Text(
                    l(
                      'I compared this draft with the current saved intervention.',
                    ),
                  ),
                ),
              if (_handoff != null)
                _field('Reason for the handoff', _reason, lines: 2, max: 1000)
              else if ((_detail != null &&
                      (_newer ?? _detail)!.toJson()['state'] == 'active') ||
                  (_learner != null && _year == config.sessionYearId)) ...[
                if (_detail == null) _field('Start date (YYYY-MM-DD)', _start),
                _field('Action to take', _action, lines: 3, max: 20000),
                _field('Review date (YYYY-MM-DD)', _review),
                if (_detail != null) ...[
                  DropdownButtonFormField<String>(
                    key: ValueKey('risk-state-$_state'),
                    initialValue: _state,
                    isExpanded: true,
                    decoration: InputDecoration(labelText: l('Review state')),
                    items: [
                      for (final entry in {
                        'active': 'Continue follow-up',
                        'completed': 'Completed',
                        'cancelled': 'Cancelled',
                      }.entries)
                        DropdownMenuItem(
                          value: entry.key,
                          child: Text(l(entry.value)),
                        ),
                    ],
                    onChanged: _busy || _uncertain
                        ? null
                        : (state) => setState(() => _state = state ?? 'active'),
                  ),
                  _field('Review outcome', _outcome, lines: 2, max: 20000),
                ],
              ],
              if (_handoff != null ||
                  (_detail != null &&
                      (_newer ?? _detail)!.toJson()['state'] == 'active') ||
                  (_learner != null && _year == config.sessionYearId))
                FilledButton(
                  onPressed: _busy ? null : _save,
                  child: Text(
                    l(
                      _uncertain
                          ? 'Retry the same change'
                          : (_handoff != null
                                ? 'Confirm owner handoff'
                                : (_detail == null
                                      ? 'Create intervention'
                                      : 'Save review')),
                    ),
                  ),
                ),
              if (_uncertain)
                TextButton(
                  onPressed: _busy ? null : _boot,
                  child: Text(l('Check saved records and discard this retry')),
                ),
              if (_newer != null && _newer!.toJson()['state'] != 'active') ...[
                Text(l('Your unsaved action draft')),
                SelectableText(_action.text),
                if (_outcome.text.isNotEmpty) SelectableText(_outcome.text),
              ],
              if (_history != null) ...[
                Text(
                  l('Saved history'),
                  style: Theme.of(context).textTheme.titleMedium,
                ),
                for (final v in _history!.versions)
                  ExpansionTile(
                    title: Text('${l('Revision')} ${v.revision}'),
                    children: [
                      Padding(
                        padding: const EdgeInsets.all(12),
                        child: SelectableText(
                          v.record.action ??
                              v.record.reason ??
                              l('Owner handoff'),
                        ),
                      ),
                    ],
                  ),
              ],
            ],
          ] else if (!_busy)
            TextButton(onPressed: _boot, child: Text(l('Try again'))),
        ],
      ),
    );
  }
}
