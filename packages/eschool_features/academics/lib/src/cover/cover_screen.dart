import 'dart:convert';

import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:uuid/uuid.dart';

import '../localization/academic_text.dart';
import 'cover_repository.dart';

class CoverScreen extends StatefulWidget {
  const CoverScreen({required this.repository, this.writable, super.key});
  final CoverRepository repository;
  final bool Function()? writable;
  @override
  State<CoverScreen> createState() => _CoverScreenState();
}

class _CoverScreenState extends State<CoverScreen> with WidgetsBindingObserver {
  CoverConfiguration? _configuration;
  CoverAbsence? _record;
  CoverCandidates? _candidates;
  List<CoverOccurrence> _mine = [];
  final _reason = TextEditingController(),
      _changeReason = TextEditingController();
  late String _from, _to, _starts, _ends;
  int? _teacher, _substitute;
  int _tab = 0, _epoch = 0;
  bool _busy = true, _hidden = true, _blocked = false, _conflict = false;
  String? _message, _pendingAction, _pendingPayload;
  int? _pendingId;
  CoverRepository get repo => widget.repository;
  String l(String value) => academicText(context, value);
  String status(Enum state) => l(switch (state.name) {
    'needsReview' => 'Needs review',
    'open' => 'Open',
    'assigned' => 'Assigned',
    'cancelled' => 'Cancelled',
    'requested' => 'Requested',
    _ => 'Status unavailable',
  });
  bool get _editable =>
      !_busy &&
      !_hidden &&
      !_blocked &&
      !_conflict &&
      _pendingPayload == null &&
      repo.isActive;
  bool get _writable => widget.writable?.call() ?? true;
  bool get _canMutate => _editable && _writable;
  static String _date(DateTime d) =>
      '${d.year.toString().padLeft(4, '0')}-${d.month.toString().padLeft(2, '0')}-${d.day.toString().padLeft(2, '0')}';
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _resetDates();
    _load();
  }

  void _resetDates() {
    final today = DateTime.now();
    _from = _starts = _ends = _date(today);
    _to = _date(today.add(const Duration(days: 30)));
  }

  @override
  void didUpdateWidget(CoverScreen oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (!identical(oldWidget.repository, repo)) {
      _clear();
      _resetDates();
      _load();
    }
  }

  void _clear() {
    _epoch++;
    _configuration = null;
    _record = null;
    _candidates = null;
    _mine = [];
    _reason.clear();
    _changeReason.clear();
    _teacher = _substitute = null;
    _pendingPayload = _pendingAction = null;
    _pendingId = null;
    _hidden = true;
    _conflict = false;
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state != AppLifecycleState.resumed) {
      setState(() {
        _epoch++;
        _hidden = true;
      });
    } else {
      _load();
    }
  }

  @override
  void dispose() {
    _epoch++;
    WidgetsBinding.instance.removeObserver(this);
    _reason.dispose();
    _changeReason.dispose();
    super.dispose();
  }

  bool _current(int epoch, CoverRepository source) =>
      mounted && epoch == _epoch && identical(source, repo) && source.isActive;
  Future<void> _load({int? id, int? page}) async {
    final range = DateTime.parse(_to).difference(DateTime.parse(_from)).inDays;
    if ((range < 0 || range > 30) && _hidden) {
      final today = DateTime.now();
      _from = _date(today);
      _to = _date(today.add(const Duration(days: 30)));
    } else if (range < 0 || range > 30) {
      setState(() {
        _busy = false;
        _message = 'Choose a window of up to 31 days.';
      });
      return;
    }
    final source = repo, epoch = ++_epoch;
    final recordId = id ?? _record?.id;
    setState(() {
      _busy = true;
      _hidden = true;
      _message = null;
    });
    try {
      final config = await source.configuration(
        _from,
        _to,
        page: page ?? _configuration?.currentPage ?? 1,
      );
      final mine = await source.mine(config.sessionYearId, _from, _to);
      final record = recordId == null ? null : await source.absence(recordId);
      if (!_current(epoch, source)) {
        return;
      }
      setState(() {
        _configuration = config;
        _mine = mine;
        _record = record;
        _candidates = null;
        _teacher = config.teachers.any((t) => t.id == _teacher)
            ? _teacher
            : config.teachers.firstOrNull?.id;
        _hidden = false;
        _blocked = false;
        _busy = false;
      });
    } catch (error) {
      if (!mounted || epoch != _epoch || !identical(source, repo)) {
        return;
      }
      final denied =
          !repo.isActive ||
          (error is ApiException && [401, 403, 404].contains(error.statusCode));
      setState(() {
        if (denied) {
          _clear();
          _blocked = true;
        }
        _hidden = true;
        _busy = false;
        _message = denied
            ? 'Your access changed. Reopen Relief cover after refreshing your account.'
            : 'Could not confirm your access. Retry to show this page.';
      });
    }
  }

  Future<void> _choose(CoverOccurrence occurrence) async {
    final source = repo, epoch = ++_epoch;
    setState(() {
      _busy = true;
      _candidates = null;
      _message = null;
    });
    try {
      final choices = await source.candidates(occurrence.id);
      if (!_current(epoch, source)) {
        return;
      }
      setState(() {
        _candidates = choices;
        _substitute = choices.teachers.firstOrNull?.id;
        _busy = false;
      });
    } catch (error) {
      if (!mounted || epoch != _epoch) {
        return;
      }
      if (error is ApiException && [401, 403, 404].contains(error.statusCode)) {
        setState(() {
          _clear();
          _blocked = true;
          _busy = false;
        });
      } else {
        setState(() {
          _busy = false;
          _message = 'Availability changed. Reload this cover request.';
        });
      }
    }
  }

  Future<void> _start(String action, int? id, Map<String, dynamic> data) async {
    if (!_canMutate) {
      return;
    }
    _pendingAction = action;
    _pendingId = id;
    _pendingPayload = jsonEncode({...data, 'operation_id': const Uuid().v4()});
    await _sendPending();
  }

  Future<void> _sendPending() async {
    if (_busy || _pendingPayload == null || !repo.isActive || !_writable) {
      return;
    }
    final source = repo, epoch = ++_epoch;
    final action = _pendingAction!, id = _pendingId, payload = _pendingPayload!;
    setState(() {
      _busy = true;
      _message = null;
    });
    try {
      await source.write(
        action,
        id,
        Map<String, dynamic>.from(jsonDecode(payload) as Map),
      );
      if (!_current(epoch, source) || _hidden) {
        return;
      }
      setState(() {
        _pendingPayload = _pendingAction = null;
        _pendingId = null;
        _reason.clear();
        _changeReason.clear();
        _busy = false;
        _conflict = false;
      });
      await _load();
    } catch (error) {
      if (!mounted || epoch != _epoch || !identical(source, repo)) {
        return;
      }
      final status = error is ApiException ? error.statusCode : null;
      if (!repo.isActive || [401, 403, 404].contains(status)) {
        setState(() {
          _clear();
          _blocked = true;
          _busy = false;
          _message =
              'Your access changed. Reopen Relief cover after refreshing your account.';
        });
      } else if (status == 409 || status == 422) {
        setState(() {
          _pendingPayload = _pendingAction = null;
          _pendingId = null;
          _busy = false;
          _conflict = true;
        });
        await _load();
        if (mounted && !_hidden) {
          setState(() {
            _message = error is ApiException && error.isApiMessage
                ? error.toString()
                : 'Cover changed or the submitted dates are invalid. Compare the current request and your draft.';
          });
        }
      } else {
        setState(() {
          _busy = false;
          _message =
              'The result is not confirmed. Retry the same change before editing.';
        });
      }
    }
  }

  Future<void> _pick(String kind) async {
    if (!_editable) {
      return;
    }
    final value = switch (kind) {
      'from' => _from,
      'to' => _to,
      'starts' => _starts,
      _ => _ends,
    };
    final chosen = await showDatePicker(
      context: context,
      initialDate: DateTime.parse(value),
      firstDate: DateTime(2000),
      lastDate: DateTime(2100),
    );
    if (!mounted || chosen == null || !_editable) {
      return;
    }
    setState(() {
      final v = _date(chosen);
      switch (kind) {
        case 'from':
          _from = v;
        case 'to':
          _to = v;
        case 'starts':
          _starts = v;
        default:
          _ends = v;
      }
    });
  }

  Widget _dateButton(String label, String value, String kind) => OutlinedButton(
    onPressed: (_editable && (kind == 'from' || kind == 'to' || _writable))
        ? () => _pick(kind)
        : null,
    child: Text('${l(label)}: $value'),
  );
  Widget _notice() => Column(
    children: [
      if (_message != null)
        Padding(
          padding: const EdgeInsets.all(12),
          child: Text(l(_message!), key: const Key('cover-message')),
        ),
      if (!_busy && _hidden && !_blocked)
        FilledButton(onPressed: _load, child: Text(l('Retry'))),
      if (_pendingPayload != null && !_busy && !_hidden)
        FilledButton(
          onPressed: _writable ? _sendPending : null,
          child: Text(l('Retry same change')),
        ),
      if (_conflict && !_hidden)
        OutlinedButton(
          onPressed: () => setState(() {
            _conflict = false;
            _message = null;
          }),
          child: Text(
            l('I compared the current request. Keep editing my draft.'),
          ),
        ),
    ],
  );
  Widget _occurrence(CoverOccurrence row, {bool manage = false}) => Card(
    child: Padding(
      padding: const EdgeInsets.all(12),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            row.source_.title,
            style: Theme.of(context).textTheme.titleMedium,
          ),
          Text(
            '${row.onDate} · ${row.source_.startsAt}–${row.source_.endsAt} · ${row.source_.roomName}',
          ),
          Text('${l('Covering')}: ${row.source_.teacherName}'),
          Text(
            '${l('Status')}: ${status(row.state)}${row.substituteName == null ? '' : ' · ${row.substituteName}'}',
          ),
          if (row.state == CoverOccurrenceStateEnum.needsReview)
            Text(
              l(
                'The timetable or availability changed. Review and refresh this request before assigning cover.',
              ),
            ),
          if (manage &&
              [
                CoverOccurrenceStateEnum.open,
                CoverOccurrenceStateEnum.assigned,
              ].contains(row.state))
            OutlinedButton(
              onPressed: _editable ? () => _choose(row) : null,
              child: Text(l('Choose substitute')),
            ),
        ],
      ),
    ),
  );
  Widget _requests() {
    final config = _configuration!;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Text(
          config.canManage
              ? l('School cover requests')
              : l('My cover requests'),
          style: Theme.of(context).textTheme.titleLarge,
        ),
        for (final item in config.absences)
          Card(
            child: ListTile(
              title: Text(
                '${item.teacherName} · ${item.startsOn} – ${item.endsOn}',
              ),
              subtitle: Text(status(item.state)),
              onTap: _editable
                  ? () {
                      _tab = 2;
                      _load(id: item.id);
                    }
                  : null,
            ),
          ),
        if (config.absences.isEmpty)
          Text(l('No cover requests in these dates.')),
        Wrap(
          spacing: 12,
          children: [
            if (config.currentPage > 1)
              TextButton(
                onPressed: _editable
                    ? () => _load(page: config.currentPage - 1)
                    : null,
                child: Text(l('Previous page')),
              ),
            if (config.currentPage < config.lastPage)
              TextButton(
                onPressed: _editable
                    ? () => _load(page: config.currentPage + 1)
                    : null,
                child: Text(l('Next page')),
              ),
          ],
        ),
        const SizedBox(height: 24),
        Text(l('Request cover'), style: Theme.of(context).textTheme.titleLarge),
        DropdownButtonFormField<int>(
          initialValue: _teacher,
          isExpanded: true,
          decoration: InputDecoration(labelText: l('Absent teacher')),
          items: config.teachers
              .map((t) => DropdownMenuItem(value: t.id, child: Text(t.name)))
              .toList(),
          onChanged: _canMutate ? (v) => setState(() => _teacher = v) : null,
        ),
        Wrap(
          spacing: 12,
          children: [
            _dateButton('First absence date', _starts, 'starts'),
            _dateButton('Last absence date', _ends, 'ends'),
          ],
        ),
        TextField(
          controller: _reason,
          readOnly: !_canMutate,
          maxLength: 500,
          maxLines: 3,
          decoration: InputDecoration(
            labelText: l('Private reason (optional)'),
          ),
        ),
        Text(
          l(
            'Private reasons are visible only to the requester and authorized managers.',
          ),
        ),
        FilledButton(
          onPressed: _canMutate && _teacher != null
              ? () => _start('request', null, {
                  'session_year_id': config.sessionYearId,
                  'teacher_id': _teacher,
                  'starts_on': _starts,
                  'ends_on': _ends,
                  'reason': _reason.text,
                })
              : null,
          child: Text(l('Request cover')),
        ),
      ],
    );
  }

  Widget _detail() {
    final record = _record;
    if (record == null) {
      return Text(l('Choose a cover request.'));
    }
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Text(
          '${record.teacherName} · ${record.startsOn} – ${record.endsOn}',
          style: Theme.of(context).textTheme.titleLarge,
        ),
        Text('${l('Revision')} ${record.revision} · ${status(record.state)}'),
        if (record.reason != null)
          Text('${l('Private reason')}: ${record.reason}'),
        if (record.cancelReason != null)
          Text('${l('Cancellation reason')}: ${record.cancelReason}'),
        for (final occurrence in record.occurrences)
          _occurrence(occurrence, manage: _configuration!.canManage),
        if (_candidates case final candidates?) ...[
          DropdownButtonFormField<int>(
            initialValue: _substitute,
            isExpanded: true,
            decoration: InputDecoration(labelText: l('Available substitute')),
            items: candidates.teachers
                .map((t) => DropdownMenuItem(value: t.id, child: Text(t.name)))
                .toList(),
            onChanged: _canMutate
                ? (v) => setState(() => _substitute = v)
                : null,
          ),
          if (candidates.teachers.isEmpty)
            Text(
              l(
                'No available teachers. Review their timetables and absence requests.',
              ),
            ),
          FilledButton(
            onPressed: _canMutate && _substitute != null
                ? () => _start('assign', candidates.occurrenceId, {
                    'revision': candidates.revision,
                    'substitute_teacher_id': _substitute,
                  })
                : null,
            child: Text(l('Assign substitute')),
          ),
        ],
        if (record.state == CoverAbsenceStateEnum.requested) ...[
          TextField(
            controller: _changeReason,
            readOnly: !_canMutate,
            maxLength: 500,
            maxLines: 2,
            onChanged: (_) => setState(() {}),
            decoration: InputDecoration(labelText: l('Reason for this change')),
          ),
          Wrap(
            spacing: 12,
            children: [
              for (final action in ['refresh', 'cancel'])
                OutlinedButton(
                  onPressed: _canMutate && _changeReason.text.trim().isNotEmpty
                      ? () => _start(action, record.id, {
                          'revision': record.revision,
                          'reason': _changeReason.text,
                        })
                      : null,
                  child: Text(
                    l(
                      action == 'refresh'
                          ? 'Refresh timetable mapping'
                          : 'Cancel cover request',
                    ),
                  ),
                ),
            ],
          ),
        ],
      ],
    );
  }

  @override
  Widget build(BuildContext context) {
    final labels = ['My assigned lessons', 'Cover requests', 'Request details'];
    final destinations = [
      Icons.schedule,
      Icons.edit_calendar,
      Icons.description,
    ];
    final visible =
        !_hidden && !_blocked && _configuration != null && repo.isActive;
    final body = Column(
      children: [
        if (_busy) const LinearProgressIndicator(),
        _notice(),
        if (visible)
          Expanded(
            child: ListView(
              padding: const EdgeInsets.all(16),
              children: [
                Text(
                  _configuration!.yearName,
                  style: Theme.of(context).textTheme.titleMedium,
                ),
                Wrap(
                  spacing: 12,
                  children: [
                    _dateButton('From date', _from, 'from'),
                    _dateButton('To date', _to, 'to'),
                    TextButton(
                      onPressed: _editable ? () => _load(page: 1) : null,
                      child: Text(l('View cover')),
                    ),
                  ],
                ),
                if (_tab == 0) ...[
                  if (_mine.isEmpty)
                    Text(l('No assigned cover in these dates.')),
                  for (final item in _mine) _occurrence(item),
                ] else if (_tab == 1)
                  _requests()
                else
                  _detail(),
              ],
            ),
          ),
      ],
    );
    return LayoutBuilder(
      builder: (context, bounds) {
        final wide = bounds.maxWidth >= 820;
        return Scaffold(
          appBar: AppBar(title: Text(l('Relief cover'))),
          body: Row(
            children: [
              if (wide)
                NavigationRail(
                  selectedIndex: _tab,
                  onDestinationSelected: _editable
                      ? (v) => setState(() => _tab = v)
                      : null,
                  labelType: NavigationRailLabelType.all,
                  destinations: [
                    for (var i = 0; i < labels.length; i++)
                      NavigationRailDestination(
                        icon: Icon(destinations[i]),
                        label: Text(l(labels[i])),
                      ),
                  ],
                ),
              Expanded(child: body),
            ],
          ),
          bottomNavigationBar: wide
              ? null
              : NavigationBar(
                  selectedIndex: _tab,
                  onDestinationSelected: _editable
                      ? (v) => setState(() => _tab = v)
                      : null,
                  destinations: [
                    for (var i = 0; i < labels.length; i++)
                      NavigationDestination(
                        icon: Icon(destinations[i]),
                        label: l(labels[i]),
                      ),
                  ],
                ),
        );
      },
    );
  }
}
