import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'register_repository.dart';
import 'register_store.dart';
import 'register_screen.dart';

class RegisterHubScreen extends StatefulWidget {
  const RegisterHubScreen({
    required this.repository,
    required this.store,
    super.key,
  });
  final RegisterRepository repository;
  final Future<RegisterStore> Function() store;
  @override
  State<RegisterHubScreen> createState() => _RegisterHubScreenState();
}

class _RegisterHubScreenState extends State<RegisterHubScreen> {
  RegisterStore? _store;
  AcademicRegisterChoicesData? _choices;
  List<Map<String, dynamic>> _cached = [];
  bool _loading = true, _busy = false, _offline = false;
  String? _error, _today;
  int _generation = 0;
  String get _choiceKey => '${widget.repository.schoolId}:register-choices';
  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _index() async {
    final prefix = '${widget.repository.schoolId}:register:';
    final cached = <Map<String, dynamic>>[];
    for (final key in await _store!.keys()) {
      if (!key.startsWith(prefix)) continue;
      final bundle = await _store!.read(key);
      if (bundle != null && bundle['matrix'] is Map) cached.add(bundle);
    }
    cached.sort(
      (a, b) => '${(b['matrix'] as Map)['on_date']}'.compareTo(
        '${(a['matrix'] as Map)['on_date']}',
      ),
    );
    _cached = cached;
  }

  Future<void> _load({String? date, int? year}) async {
    final generation = ++_generation;
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      _store ??= await widget.store();
      if (!mounted || generation != _generation) return;
      AcademicRegisterChoicesData choices;
      var offline = false;
      try {
        choices = await widget.repository.choices(date: date, year: year);
        if (!mounted || generation != _generation) return;
        await _store!.write(_choiceKey, choices.toJson());
      } catch (error) {
        if (error is ApiException &&
            [401, 403, 404, 422].contains(error.statusCode)) {
          rethrow;
        }
        final kept = await _store!.read(_choiceKey);
        if (kept == null) rethrow;
        choices = AcademicRegisterChoicesData.fromJson(kept);
        offline = true;
      }
      await _index();
      if (!mounted || generation != _generation) return;
      setState(() {
        _choices = choices;
        _today ??= choices.onDate;
        _loading = false;
        _offline = offline;
      });
    } catch (error) {
      if (!mounted || generation != _generation) return;
      setState(() {
        _choices = null;
        _cached = [];
        _loading = false;
        _error = error is ApiException && error.isApiMessage
            ? error.errorMessage
            : 'Could not open academic registers. Check the connection and retry.';
      });
    }
  }

  Future<void> _date() async {
    if (_choices == null) return;
    final current = DateTime.parse(_choices!.onDate);
    final value = await showDatePicker(
      context: context,
      initialDate: current,
      firstDate: DateTime(2000),
      lastDate: DateTime.parse(_today!),
    );
    if (value != null) {
      await _load(
        date:
            '${value.year.toString().padLeft(4, '0')}-${value.month.toString().padLeft(2, '0')}-${value.day.toString().padLeft(2, '0')}',
      );
    }
  }

  Future<void> _open({
    int? homeroom,
    int? lesson,
    Map<String, dynamic>? cached,
  }) async {
    if (_busy || _choices == null || _store == null) return;
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      AcademicRegisterMatrixData matrix;
      var fresh = false;
      if (cached != null) {
        matrix = AcademicRegisterMatrixData.fromJson(
          Map<String, dynamic>.from(cached['matrix'] as Map),
        );
      } else {
        try {
          matrix = await widget.repository.open(
            AcademicRegisterTargetWrite(
              sessionYearId: _choices!.sessionYearId,
              onDate: _choices!.onDate,
              classSectionId: homeroom,
              timetableLessonId: lesson,
            ),
          );
          fresh = true;
        } catch (error) {
          if (error is ApiException &&
              [401, 403, 404, 409, 422].contains(error.statusCode)) {
            rethrow;
          }
          final matches = _cached.where((bundle) {
            final row = bundle['matrix'] as Map;
            return row['session_year_id'] == _choices!.sessionYearId &&
                row['on_date'] == _choices!.onDate &&
                (lesson != null || row['class_section_id'] == homeroom) &&
                row['timetable_lesson_id'] == lesson;
          });
          if (matches.isEmpty) rethrow;
          matrix = AcademicRegisterMatrixData.fromJson(
            Map<String, dynamic>.from(matches.first['matrix'] as Map),
          );
        }
      }
      if (!mounted) return;
      await Navigator.of(context).push(
        AdaptivePageRoute(
          builder: (_) => RegisterScreen.withRepository(
            widget.repository,
            _store!,
            matrix.registerId,
            initial: fresh ? matrix : null,
          ),
        ),
      );
      await _index();
      if (mounted) setState(() {});
    } catch (error) {
      if (mounted) {
        setState(() {
          _error = error is ApiException && error.isApiMessage
              ? error.errorMessage
              : 'Could not open this register. Connect once to prepare it for offline use.';
        });
      }
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  void dispose() {
    _generation++;
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final choices = _choices;
    final body = ScreenScaffold(
      title: 'Academic registers',
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : choices == null
          ? Center(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(_error ?? 'No registers are available.'),
                  TextButton(onPressed: _load, child: const Text('Try again')),
                ],
              ),
            )
          : ListView(
              padding: const EdgeInsets.all(16),
              children: [
                if (_busy) const LinearProgressIndicator(),
                Wrap(
                  spacing: 12,
                  runSpacing: 8,
                  crossAxisAlignment: WrapCrossAlignment.center,
                  children: [
                    TextButton.icon(
                      onPressed: _busy ? null : _date,
                      icon: const Icon(Icons.calendar_today),
                      label: Text(choices.onDate),
                    ),
                    TextButton(
                      onPressed: _busy ? null : _load,
                      child: const Text('Today'),
                    ),
                    SizedBox(
                      width: 200,
                      child: DropdownButtonFormField<int>(
                        key: ValueKey(
                          'year:${choices.onDate}:${choices.sessionYearId}',
                        ),
                        initialValue: choices.sessionYearId,
                        isExpanded: true,
                        decoration: const InputDecoration(
                          labelText: 'School year',
                        ),
                        items: choices.schoolYears
                            .map(
                              (year) => DropdownMenuItem(
                                value: year.id,
                                child: Text(year.name),
                              ),
                            )
                            .toList(),
                        onChanged: _busy
                            ? null
                            : (year) {
                                if (year != null) {
                                  _load(date: choices.onDate, year: year);
                                }
                              },
                      ),
                    ),
                  ],
                ),
                if (_offline)
                  const Padding(
                    padding: EdgeInsets.symmetric(vertical: 12),
                    child: Text(
                      'Offline copy. Open a previously prepared register; saved changes sync after current permissions are checked.',
                    ),
                  ),
                if (_error != null)
                  Padding(
                    padding: const EdgeInsets.symmetric(vertical: 12),
                    child: Text(
                      _error!,
                      style: TextStyle(
                        color: Theme.of(context).colorScheme.error,
                      ),
                    ),
                  ),
                const SizedBox(height: 20),
                Text(
                  'Homeroom registers',
                  style: Theme.of(context).textTheme.titleLarge,
                ),
                if (choices.homerooms.isEmpty)
                  const Text('No homeroom register is assigned for this day.'),
                for (final room in choices.homerooms)
                  Card(
                    child: ListTile(
                      leading: const Icon(Icons.groups_outlined),
                      title: Text(room.name),
                      subtitle: Text(choices.onDate),
                      trailing: const Icon(Icons.chevron_right),
                      onTap: _busy ? null : () => _open(homeroom: room.id),
                    ),
                  ),
                const SizedBox(height: 20),
                Text(
                  'Period registers',
                  style: Theme.of(context).textTheme.titleLarge,
                ),
                if (choices.periods.isEmpty)
                  const Text('No period register is assigned for this day.'),
                for (final period in choices.periods)
                  Card(
                    child: ListTile(
                      leading: const Icon(Icons.schedule),
                      title: Text(period.name),
                      trailing: const Icon(Icons.chevron_right),
                      onTap: _busy ? null : () => _open(lesson: period.id),
                    ),
                  ),
                if (_cached.isNotEmpty) ...[
                  const SizedBox(height: 20),
                  Text(
                    'Prepared on this device',
                    style: Theme.of(context).textTheme.titleLarge,
                  ),
                  for (final bundle in _cached)
                    Builder(
                      builder: (context) {
                        final row = bundle['matrix'] as Map;
                        final count = (bundle['queue'] as List? ?? []).length;
                        return Card(
                          child: ListTile(
                            title: Text('${row['label']} · ${row['on_date']}'),
                            subtitle: Text(
                              count == 0
                                  ? 'Available for offline capture'
                                  : '$count saved operations pending sync',
                            ),
                            leading: const Icon(Icons.offline_pin_outlined),
                            onTap: _busy ? null : () => _open(cached: bundle),
                          ),
                        );
                      },
                    ),
                ],
              ],
            ),
    );
    return AdaptiveScaffold(
      body: body,
      destinations: const [
        AdaptiveDestination(
          icon: Icon(Icons.home_outlined),
          selectedIcon: Icon(Icons.home),
          label: 'Home',
        ),
        AdaptiveDestination(
          icon: Icon(Icons.fact_check_outlined),
          selectedIcon: Icon(Icons.fact_check),
          label: 'Registers',
        ),
      ],
      selectedIndex: 1,
      onDestinationSelected: (index) {
        if (index == 0) Navigator.of(context).pop();
      },
    );
  }
}
