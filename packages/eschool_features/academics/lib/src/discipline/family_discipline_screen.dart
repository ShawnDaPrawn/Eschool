import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:uuid/uuid.dart';
import '../localization/academic_text.dart';
import 'discipline_repository.dart';

/// Only the published, family-safe projection is requested; staff sources are absent.
class FamilyDisciplineScreen extends StatefulWidget {
  const FamilyDisciplineScreen({
    required this.repository,
    required this.learnerId,
    required this.guardian,
    required this.canRead,
    required this.canWrite,
    super.key,
  });
  final DisciplineRepository repository;
  final int learnerId;
  final bool guardian;
  final bool Function() canRead, canWrite;
  @override
  State<FamilyDisciplineScreen> createState() => _FamilyDisciplineScreenState();
}

class _FamilyDisciplineScreenState extends State<FamilyDisciplineScreen>
    with WidgetsBindingObserver {
  DisciplineFamilyHistory? _history;
  DisciplineIntent? _pending;
  int? _year;
  int _epoch = 0, _page = 1;
  bool _busy = true, _dead = false, _suspended = false, _unknown = false;
  String _error = '';
  String l(String text) => academicText(context, text);
  bool get _active =>
      mounted &&
      !_dead &&
      !_suspended &&
      widget.canRead() &&
      widget.repository.isActive;
  bool _current(int epoch) => _active && epoch == _epoch;
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _load();
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    _epoch++;
    _history = null;
    _pending = null;
    super.dispose();
  }

  @override
  void didUpdateWidget(covariant FamilyDisciplineScreen oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.learnerId != widget.learnerId ||
        oldWidget.repository != widget.repository) {
      _history = null;
      _pending = null;
      _year = null;
      _page = 1;
      _unknown = false;
      _load();
    }
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (!mounted) return;
    if (state != AppLifecycleState.resumed) {
      setState(() {
        _suspended = true;
        _epoch++;
        _history = null;
        _pending = null;
        _unknown = false;
        _busy = false;
      });
    } else if (_suspended && !_dead) {
      _suspended = false;
      _load();
    }
  }

  void _fail(Object error, int epoch) {
    if (!mounted || epoch != _epoch) return;
    setState(() {
      _busy = false;
      if (error is ApiException && [401, 403, 404].contains(error.statusCode)) {
        _history = null;
        _pending = null;
        _unknown = false;
        _dead = true;
        _error = 'Reopen school discipline to check your access.';
      } else {
        _error = 'School discipline could not be loaded. Try again.';
      }
    });
  }

  Future<void> _load({int? year, int? page}) async {
    if (!_active || _unknown) return;
    final epoch = ++_epoch;
    setState(() {
      _busy = true;
      _error = '';
      _history = null;
      _year = year ?? _year;
      _page = page ?? _page;
    });
    try {
      final row = await widget.repository.family(
        widget.learnerId,
        _year,
        page: _page,
      );
      if (!_current(epoch)) return;
      setState(() {
        _history = row;
        _year = row.sessionYearId;
        _busy = false;
      });
    } catch (e) {
      _fail(e, epoch);
    }
  }

  Future<void> _acknowledge(DisciplineFamilyRecord row) async {
    if (!_active || _busy || !widget.guardian || !widget.canWrite()) return;
    final intent = DisciplineIntent(DisciplineAction.acknowledge, {
      'operation_id': const Uuid().v4(),
      'expected_revision': row.revision,
    }, record: row.id);
    await _send(intent);
  }

  Future<void> _send(DisciplineIntent intent) async {
    if (!_active || _busy || !widget.guardian || !widget.canWrite()) return;
    final epoch = _epoch;
    setState(() {
      _busy = true;
      _pending = intent;
      _error = '';
    });
    try {
      await widget.repository.write(intent);
      if (!_current(epoch)) return;
      setState(() {
        _busy = false;
        _pending = null;
        _unknown = false;
      });
      await _load();
    } catch (e) {
      if (!_current(epoch)) return;
      if (e is ApiException && e.statusCode == 0) {
        setState(() {
          _busy = false;
          _unknown = true;
          _error =
              'The acknowledgement outcome is unknown. Retry the exact operation.';
        });
      } else if (e is ApiException && [409, 422, 423].contains(e.statusCode)) {
        setState(() {
          _busy = false;
          _unknown = false;
          _pending = null;
          _error =
              'The entry changed or acknowledgement is unavailable. Reload the saved history.';
        });
      } else {
        _fail(e, epoch);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final history = _history;
    final years = history?.years ?? <DisciplineChoice>[];
    return ScreenScaffold(
      title: l('School discipline'),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Text(
            l(
              'Read published school records and supervised detention arrangements. Acknowledgement confirms reading, not agreement.',
            ),
          ),
          if (_busy) const LinearProgressIndicator(),
          if (_error.isNotEmpty) Text(l(_error)),
          if (_unknown && _pending != null)
            FilledButton(
              onPressed: _busy ? null : () => _send(_pending!),
              child: Text(l('Retry exact operation')),
            ),
          if (!_unknown && !_dead)
            TextButton(
              onPressed: _busy ? null : () => _load(),
              child: Text(l('Reload saved history')),
            ),
          if (history != null) ...[
            if (years.isNotEmpty)
              DropdownButtonFormField<int>(
                key: ValueKey('family-year-$_year'),
                initialValue: years.any((year) => year.id == _year)
                    ? _year
                    : null,
                decoration: InputDecoration(labelText: l('School year')),
                items: years
                    .map(
                      (row) => DropdownMenuItem(
                        value: row.id,
                        child: Text(row.name),
                      ),
                    )
                    .toList(),
                onChanged: _busy || _unknown
                    ? null
                    : (year) => _load(year: year, page: 1),
              ),
            if (history.records.isEmpty)
              Text(
                l(
                  'No published discipline entries are available for this year.',
                ),
              ),
            for (final row in history.records)
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      Text(
                        '${row.occurredOn} · ${row.infraction}',
                        style: Theme.of(context).textTheme.titleMedium,
                      ),
                      Text(row.level),
                      SelectableText(row.description),
                      if (row.sanction != null) Text(row.sanction!),
                      if (row.familyReason != null)
                        SelectableText(row.familyReason!),
                      if (row.toJson()['status'] == 'overturned')
                        Text(l('Overturned with a retained history')),
                      for (final detention in row.detentions) ...[
                        Text(
                          '${detention.onDate} ${detention.startsAt}–${detention.endsAt} · ${detention.timezone}',
                        ),
                        Text(detention.location),
                        Text(
                          detention.active
                              ? l('Confirmed supervised session')
                              : l('Session no longer active'),
                        ),
                        Text(
                          detention.attendance == null
                              ? l('Not captured')
                              : l(detention.toJson()['attendance'].toString()),
                        ),
                      ],
                      if (widget.guardian) ...[
                        if (row.acknowledged)
                          Text(l('You acknowledged this revision'))
                        else
                          FilledButton(
                            onPressed: _busy || _unknown || !widget.canWrite()
                                ? null
                                : () => _acknowledge(row),
                            child: Text(l('Acknowledge reading this revision')),
                          ),
                      ],
                    ],
                  ),
                ),
              ),
            Wrap(
              spacing: 12,
              children: [
                if (history.page > 1)
                  TextButton(
                    onPressed: _busy || _unknown
                        ? null
                        : () => _load(page: history.page - 1),
                    child: Text(l('Previous')),
                  ),
                Text('${history.page} / ${history.lastPage}'),
                if (history.page < history.lastPage)
                  TextButton(
                    onPressed: _busy || _unknown
                        ? null
                        : () => _load(page: history.page + 1),
                    child: Text(l('Next')),
                  ),
              ],
            ),
          ],
        ],
      ),
    );
  }
}
