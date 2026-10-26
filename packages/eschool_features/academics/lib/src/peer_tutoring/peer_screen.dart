import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:uuid/uuid.dart';
import '../localization/academic_text.dart';
import '../wellbeing/school_wall_time.dart';
import 'peer_pending.dart';
import 'peer_repository.dart';

class PeerTutoringScreen extends StatefulWidget {
  const PeerTutoringScreen({required this.repository, this.now, super.key});
  final PeerRepository repository;
  final DateTime Function()? now;
  @override
  State<PeerTutoringScreen> createState() => _PeerTutoringScreenState();
}

class _PeerTutoringScreenState extends State<PeerTutoringScreen>
    with WidgetsBindingObserver {
  PeerConfiguration? _config;
  final _rows = <PeerSession>[],
      _roster = <PeerChoice>[],
      _matches = <PeerMatch>[];
  final _venue = TextEditingController(),
      _start = TextEditingController(),
      _end = TextEditingController();
  final _minutes = TextEditingController(),
      _reason = TextEditingController(),
      _points = TextEditingController();
  final _scroll = ScrollController();
  PeerPending? _pending;
  int? _group, _learner, _child, _next, _category;
  PeerMatch? _match;
  PeerSession? _selected;
  PeerAction? _action;
  int _generation = 0;
  bool _foreground = true, _loading = false, _busy = false;
  String? _error, _notice;
  String l(String text) => academicText(context, text);
  bool get _staff => widget.repository.persona == 'staff';
  bool get _idle => !_busy && !_loading && _pending == null && _config != null;
  bool _active(int generation) =>
      mounted &&
      _foreground &&
      generation == _generation &&
      widget.repository.isActive;
  void _erase() {
    _config = null;
    _rows.clear();
    _roster.clear();
    _matches.clear();
    _group = null;
    _learner = null;
    _child = null;
    _next = null;
    _match = null;
    _selected = null;
    _action = null;
    _category = null;
    for (final c in [_venue, _start, _end, _minutes, _reason, _points]) {
      c.clear();
    }
    _error = null;
    _notice = null;
    _loading = false;
    _busy = false;
  }

  bool _accept(int generation) {
    if (_active(generation)) return true;
    if (mounted &&
        _foreground &&
        generation == _generation &&
        !widget.repository.isActive) {
      setState(() {
        _erase();
        _pending?.close();
        _pending = null;
        _error = 'Your peer tutoring access changed. Reopen this screen.';
      });
    }
    return false;
  }

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _load();
  }

  @override
  void didUpdateWidget(PeerTutoringScreen old) {
    super.didUpdateWidget(old);
    if (old.repository != widget.repository) {
      _generation++;
      _pending?.close();
      _pending = null;
      _erase();
      _load();
    }
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state != AppLifecycleState.resumed) {
      _generation++;
      setState(() {
        _foreground = false;
        _erase();
      });
    } else {
      _foreground = true;
      _load();
    }
  }

  @override
  void dispose() {
    _generation++;
    WidgetsBinding.instance.removeObserver(this);
    _pending?.close();
    _erase();
    for (final c in [_venue, _start, _end, _minutes, _reason, _points]) {
      c.dispose();
    }
    _scroll.dispose();
    super.dispose();
  }

  void _fail(Object error, int generation, {bool uncertain = false}) {
    if (!mounted || !_foreground || generation != _generation) return;
    setState(() {
      final denied =
          !widget.repository.isActive ||
          error is ApiException && [401, 403, 404].contains(error.statusCode);
      if (denied) {
        _erase();
        _pending?.close();
        _pending = null;
      }
      _busy = false;
      _loading = false;
      _error = denied
          ? 'Your peer tutoring access changed. Reopen this screen.'
          : uncertain
          ? 'The request outcome is uncertain. Retry the original request.'
          : error is FormatException
          ? error.message
          : error is ApiException && error.isApiMessage
          ? error.errorMessage
          : 'Peer tutoring could not be loaded. Try again.';
    });
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (_active(generation) && _scroll.hasClients) _scroll.jumpTo(0);
    });
  }

  Future<void> _load({bool reconcile = false}) async {
    if (!_foreground || _busy) return;
    final generation = ++_generation;
    setState(() {
      _erase();
      _loading = true;
    });
    try {
      final config = await widget.repository.configuration();
      if (!_accept(generation)) return;
      SchoolWallTime.local(
        (widget.now?.call() ?? DateTime.now()).toUtc(),
        config.timezone,
      );
      if (_pending != null && (_pending!.expired || !_staff)) {
        _pending!.close();
        _pending = null;
        if (!reconcile) {
          _config = config;
          throw const FormatException(
            'The retry window expired. Refresh the session history before starting another request.',
          );
        }
      }
      _config = config;
      final page = await widget.repository.sessions();
      if (!_accept(generation)) return;
      setState(() {
        _rows.addAll(page.items);
        _next = page.nextPage;
        _child = config.children.isEmpty ? null : config.children.first.id;
        _loading = false;
      });
    } catch (error) {
      _fail(error, generation);
    }
  }

  Future<void> _more() async {
    if (!_idle || _next == null) return;
    final generation = _generation, page = _next!;
    setState(() {
      _loading = true;
    });
    try {
      final result = await widget.repository.sessions(page: page);
      if (!_accept(generation)) return;
      if (result.items.any((row) => _rows.any((old) => old.id == row.id)))
        throw const FormatException('Refresh the session history.');
      setState(() {
        _rows.addAll(result.items);
        _next = result.nextPage;
        _loading = false;
      });
    } catch (error) {
      _fail(error, generation);
    }
  }

  Future<void> _chooseGroup(int? group) async {
    if (!_idle || group == null) return;
    final generation = ++_generation;
    setState(() {
      _group = group;
      _learner = null;
      _match = null;
      _roster.clear();
      _matches.clear();
      _loading = true;
      _error = null;
    });
    try {
      final result = await widget.repository.roster(group);
      if (!_accept(generation)) return;
      setState(() {
        _roster.addAll(result.items);
        _loading = false;
      });
    } catch (error) {
      _fail(error, generation);
    }
  }

  Future<void> _chooseLearner(int? learner) async {
    if (!_idle || learner == null || _group == null) return;
    final generation = ++_generation, group = _group!;
    setState(() {
      _learner = learner;
      _match = null;
      _matches.clear();
      _loading = true;
      _error = null;
    });
    try {
      final result = await widget.repository.matches(group, learner);
      if (!_accept(generation)) return;
      setState(() {
        _matches.addAll(result.items);
        _loading = false;
      });
    } catch (error) {
      _fail(error, generation);
    }
  }

  Future<void> _submit(PeerIntent intent, {bool retry = false}) async {
    if (!_foreground || _busy || _loading || (!_idle && !retry)) return;
    final generation = _generation;
    PeerPending? submitted;
    try {
      submitted = retry ? _pending : PeerPending(widget.repository, intent);
      if (submitted == null) return;
      setState(() {
        _busy = true;
        _error = null;
        _notice = null;
        _pending = submitted;
      });
      final result = await widget.repository.execute(intent);
      if (!_accept(generation)) return;
      submitted.close();
      setState(() {
        _pending = null;
        _busy = false;
        _selected = null;
        _action = null;
        _reason.clear();
        _minutes.clear();
        _points.clear();
        _category = null;
        _venue.clear();
        _start.clear();
        _end.clear();
        _match = null;
        _matches.clear();
        final index = _rows.indexWhere((row) => row.id == result.id);
        if (index < 0) {
          _rows.insert(0, result);
        } else {
          _rows[index] = result;
        }
        _notice = 'Peer session updated.';
      });
    } catch (error) {
      if (!mounted || !_foreground || generation != _generation) return;
      final uncertain =
          !(error is ApiException &&
              error.statusCode != null &&
              error.statusCode! >= 400 &&
              error.statusCode! < 500 &&
              ![408, 425, 429].contains(error.statusCode));
      if (!uncertain) {
        submitted?.close();
        _pending = null;
      }
      _fail(error, generation, uncertain: uncertain);
    }
  }

  void _create() {
    if (!_idle || _config?.writable != true || _match == null || _group == null)
      return;
    try {
      final start = SchoolWallTime.utc(_start.text.trim(), _config!.timezone),
          end = SchoolWallTime.utc(_end.text.trim(), _config!.timezone);
      final now = (widget.now?.call() ?? DateTime.now()).toUtc();
      if (!start.isAfter(now) ||
          !end.isAfter(start) ||
          end.difference(start).inMinutes > 180 ||
          _venue.text.trim().isEmpty) {
        throw const FormatException(
          'Choose a future peer session of up to three hours.',
        );
      }
      _submit(
        PeerIntent(
          action: PeerAction.create,
          body: {
            'persona': 'staff',
            'operation_id': const Uuid().v4(),
            'teaching_group_id': _group,
            'tutor_learner_id': _match!.tutorLearnerId,
            'tutee_learner_id': _match!.tuteeLearnerId,
            'topic_id': _match!.topicId,
            'starts_at': _start.text.trim(),
            'ends_at': _end.text.trim(),
            'venue': _venue.text.trim(),
          },
        ),
      );
    } catch (error) {
      _fail(error, _generation);
    }
  }

  void _change() {
    final row = _selected, action = _action;
    if (!_idle || row == null || action == null) return;
    try {
      final body = <String, dynamic>{
        'persona': 'staff',
        'operation_id': const Uuid().v4(),
        'revision': row.revision,
      };
      if (action == PeerAction.complete) {
        final minutes = int.tryParse(_minutes.text.trim());
        if (minutes == null ||
            minutes < 1 ||
            minutes > row.endsAt.difference(row.startsAt).inMinutes)
          throw const FormatException(
            'Enter verified service minutes within the scheduled duration.',
          );
        body['minutes'] = minutes;
        if (_category != null || _points.text.trim().isNotEmpty) {
          final points = int.tryParse(_points.text.trim());
          final category = _config!.pointCategories
              .where((c) => c.id == _category)
              .firstOrNull;
          if (!_config!.canAwardPoints ||
              category == null ||
              points == null ||
              points < category.minimumPoints ||
              points > category.maximumPoints ||
              points < 1)
            throw const FormatException(
              'Choose a permitted point category and integer points, or neither.',
            );
          body.addAll({'point_category_id': _category, 'points': points});
        }
      } else {
        if (_reason.text.trim().isEmpty)
          throw const FormatException(
            'Give a reason for the cancellation or correction.',
          );
        body['reason'] = _reason.text.trim();
      }
      _submit(PeerIntent(action: action, target: row.id, body: body));
    } catch (error) {
      _fail(error, _generation);
    }
  }

  Future<void> _retry() async {
    final pending = _pending;
    if (pending == null || _busy || _loading) return;
    if (pending.expired) {
      pending.close();
      _pending = null;
      _load(reconcile: true);
      return;
    }
    try {
      final intent = pending.open(
        widget.repository,
        authorized: _config != null && _staff,
      );
      await _submit(intent, retry: true);
    } catch (error) {
      pending.close();
      _pending = null;
      _fail(error, _generation);
    }
  }

  Widget _field(
    TextEditingController controller,
    String label, {
    int limit = 191,
    bool integer = false,
  }) => Padding(
    padding: const EdgeInsets.symmetric(vertical: 8),
    child: TextField(
      controller: controller,
      enabled: _idle,
      maxLength: limit,
      keyboardType: integer ? TextInputType.number : TextInputType.text,
      decoration: InputDecoration(
        labelText: l(label),
        border: const OutlineInputBorder(),
      ),
    ),
  );
  Widget _select<T>(
    String label,
    T? value,
    List<DropdownMenuItem<T>> items,
    void Function(T?) onChanged,
  ) => Padding(
    padding: const EdgeInsets.symmetric(vertical: 8),
    child: DropdownButtonFormField<T>(
      key: ValueKey('$label:$value:${items.length}'),
      initialValue: value,
      isExpanded: true,
      decoration: InputDecoration(
        labelText: l(label),
        border: const OutlineInputBorder(),
      ),
      items: items,
      onChanged: _idle ? onChanged : null,
    ),
  );
  Widget _editor() => Card(
    child: Padding(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          const AcademicText('Schedule supervised peer tutoring'),
          _select<int>(
            'Teaching group',
            _group,
            _config!.groups
                .map(
                  (g) => DropdownMenuItem(
                    value: g.id,
                    child: Text(g.name, overflow: TextOverflow.ellipsis),
                  ),
                )
                .toList(),
            _chooseGroup,
          ),
          if (_group != null)
            _select<int>(
              'Learner needing support',
              _learner,
              _roster
                  .map(
                    (g) => DropdownMenuItem(
                      value: g.id,
                      child: Text(g.name, overflow: TextOverflow.ellipsis),
                    ),
                  )
                  .toList(),
              _chooseLearner,
            ),
          if (_learner != null) ...[
            const AcademicText(
              'Suggested tutors use current topic evidence. The supervisor must confirm the arrangement.',
            ),
            _select<PeerMatch>(
              'Tutor and topic',
              _match,
              _matches
                  .map(
                    (m) => DropdownMenuItem(
                      value: m,
                      child: Text(
                        '${m.tutorName} · ${m.topicName}',
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                  )
                  .toList(),
              (m) => setState(() => _match = m),
            ),
            if (_matches.isEmpty)
              const AcademicText(
                'No current evidenced tutor matches for this learner.',
              ),
          ],
          Text('${l('School time zone')}: ${_config!.timezone}'),
          _field(_start, 'Starts at (YYYY-MM-DDTHH:MM)', limit: 16),
          _field(_end, 'Ends at (YYYY-MM-DDTHH:MM)', limit: 16),
          _field(_venue, 'Venue'),
          FilledButton(
            onPressed: _idle && _match != null ? _create : null,
            child: const AcademicText('Schedule peer session'),
          ),
        ],
      ),
    ),
  );
  Widget _correction() => Card(
    child: Padding(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Text(
            '${_selected!.topicName} · ${l(_action == PeerAction.complete
                ? 'Confirm supervised service'
                : _action == PeerAction.cancel
                ? 'Cancel peer session'
                : 'Correct completed service')}',
          ),
          if (_action == PeerAction.complete) ...[
            _field(
              _minutes,
              'Verified service minutes',
              integer: true,
              limit: 3,
            ),
            if (_config!.canAwardPoints) ...[
              _select<int>(
                'Optional house point category',
                _category,
                [
                  DropdownMenuItem<int>(
                    value: null,
                    child: Text(l('No house point award')),
                  ),
                  ..._config!.pointCategories.map(
                    (c) => DropdownMenuItem(
                      value: c.id,
                      child: Text(c.name, overflow: TextOverflow.ellipsis),
                    ),
                  ),
                ],
                (v) => setState(() {
                  _category = v;
                  _points.clear();
                }),
              ),
              _field(
                _points,
                'Optional integer house points',
                integer: true,
                limit: 5,
              ),
            ],
            const AcademicText(
              'Confirm only service you supervised. This does not change mastery or marks.',
            ),
          ] else
            _field(_reason, 'Reason', limit: 1000),
          FilledButton(
            onPressed: _idle ? _change : null,
            child: const AcademicText('Confirm session change'),
          ),
          TextButton(
            onPressed: _idle
                ? () => setState(() {
                    _selected = null;
                    _action = null;
                    _reason.clear();
                    _minutes.clear();
                    _points.clear();
                    _category = null;
                  })
                : null,
            child: const AcademicText('Back to peer sessions'),
          ),
        ],
      ),
    ),
  );
  void _open(PeerSession row, PeerAction action) => setState(() {
    _selected = row;
    _action = action;
    _minutes.clear();
    _reason.clear();
    _points.clear();
    _category = null;
  });
  Widget _row(PeerSession row) {
    final showService = _staff || _child == row.tutorLearnerId;
    final zone = _config!.timezone;
    return Card(
      key: ValueKey('peer-session:${row.id}'),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Text(row.topicName, style: Theme.of(context).textTheme.titleMedium),
            Text(
              '${row.tutorName ?? l('Learner')} → ${row.tuteeName ?? l('Learner')}',
            ),
            Text('${l('Supervisor')}: ${row.supervisorName}'),
            Text(
              '${SchoolWallTime.local(row.startsAt, zone)} — ${SchoolWallTime.local(row.endsAt, zone)} ($zone)',
            ),
            Text(row.venue),
            Text(l(row.status.value)),
            if (showService &&
                row.serviceForReader &&
                row.serviceMinutes != null)
              Text('${l('Verified service minutes')}: ${row.serviceMinutes}'),
            if (showService && row.housePointId != null)
              const AcademicText('House points recorded under school policy.'),
            if (_staff)
              Wrap(
                spacing: 8,
                children: [
                  if (row.canComplete)
                    FilledButton(
                      onPressed: _idle
                          ? () => _open(row, PeerAction.complete)
                          : null,
                      child: const AcademicText('Confirm supervised service'),
                    ),
                  if (row.canCancel)
                    OutlinedButton(
                      onPressed: _idle
                          ? () => _open(row, PeerAction.cancel)
                          : null,
                      child: const AcademicText('Cancel peer session'),
                    ),
                  if (row.canReverse)
                    OutlinedButton(
                      onPressed: _idle
                          ? () => _open(row, PeerAction.reverse)
                          : null,
                      child: const AcademicText('Correct completed service'),
                    ),
                ],
              ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) => ScreenScaffold(
    title: l('Peer tutoring'),
    body: !_foreground
        ? const SizedBox.shrink()
        : ListView(
            controller: _scroll,
            padding: const EdgeInsets.all(16),
            children: [
              if (_loading || _busy) const LinearProgressIndicator(),
              if (_error != null)
                Text(
                  l(_error!),
                  style: TextStyle(color: Theme.of(context).colorScheme.error),
                ),
              if (_notice != null) Text(l(_notice!)),
              if (_pending != null) ...[
                const AcademicText(
                  'A submitted request needs reconciliation before another request.',
                ),
                FilledButton(
                  onPressed: !_busy && !_loading ? _retry : null,
                  child: AcademicText(
                    _pending!.expired
                        ? 'Refresh session history'
                        : 'Retry original request',
                  ),
                ),
              ] else
                TextButton(
                  onPressed: !_busy && !_loading
                      ? () => _load(reconcile: true)
                      : null,
                  child: const AcademicText('Refresh peer sessions'),
                ),
              if (_config != null) ...[
                if (!_staff && _config!.children.isNotEmpty)
                  _select<int>(
                    'Learner',
                    _child,
                    _config!.children
                        .map(
                          (c) => DropdownMenuItem(
                            value: c.id,
                            child: Text(
                              c.name,
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                        )
                        .toList(),
                    (v) => setState(() => _child = v),
                  ),
                if (_staff && _selected != null)
                  _correction()
                else if (_staff && _config!.writable)
                  _editor(),
                const AcademicText('Peer sessions'),
                ..._rows
                    .where(
                      (row) =>
                          _staff ||
                          row.tutorLearnerId == _child ||
                          row.tuteeLearnerId == _child,
                    )
                    .map(_row),
                if (_rows.isEmpty)
                  const AcademicText('No peer sessions are available.'),
                if (_next != null)
                  OutlinedButton(
                    onPressed: _idle ? _more : null,
                    child: const AcademicText('More peer sessions'),
                  ),
              ],
            ],
          ),
  );
}
