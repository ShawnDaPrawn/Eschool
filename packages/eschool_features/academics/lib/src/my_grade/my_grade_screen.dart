import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';

import '../localization/academic_text.dart';
import 'my_grade_repository.dart';

/// Online grade-head workbench. Every action opens its own fresh authorized reader.
class MyGradeScreen extends StatefulWidget {
  const MyGradeScreen({
    required this.repository,
    required this.onRegister,
    required this.onDiscipline,
    required this.onMessage,
    required this.onRisk,
    required this.onAwards,
    super.key,
  });
  final MyGradeRepository repository;
  final Future<void> Function(MyGradeLearner learner, int gradeId) onRegister;
  final Future<void> Function(MyGradePendingRecord record, int gradeId)
  onDiscipline;
  final Future<void> Function(int gradeId) onMessage, onRisk, onAwards;
  @override
  State<MyGradeScreen> createState() => _MyGradeScreenState();
}

class _MyGradeScreenState extends State<MyGradeScreen>
    with WidgetsBindingObserver {
  MyGradeAccess? _access;
  MyGradeDashboard? _data;
  int? _grade;
  int _epoch = 0;
  bool _busy = true, _hidden = false;
  String? _error;
  bool get _active => mounted && !_hidden && widget.repository.isActive;
  bool _current(int epoch) => _active && epoch == _epoch;
  String l(String key) => academicText(context, key);

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _load();
  }

  @override
  void dispose() {
    _epoch++;
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      _hidden = false;
      _load();
    } else {
      _epoch++;
      _hidden = true;
      if (mounted) {
        setState(() {
          _access = null;
          _data = null;
          _error = null;
          _busy = true;
        });
      }
    }
  }

  Future<void> _load({int? grade, int page = 1, int disciplinePage = 1}) async {
    final epoch = ++_epoch;
    if (!_active) return;
    setState(() {
      _busy = true;
      _error = null;
      _data = null;
    });
    try {
      final access = await widget.repository.access();
      if (!_current(epoch)) return;
      final chosen = grade ?? _grade;
      final selected = access.grades.any((g) => g.id == chosen)
          ? chosen
          : (access.grades.isEmpty ? null : access.grades.first.id);
      final data = selected == null
          ? null
          : await widget.repository.dashboard(
              selected,
              page: page,
              disciplinePage: disciplinePage,
            );
      if (!_current(epoch)) return;
      setState(() {
        _access = access;
        _grade = selected;
        _data = data;
        _busy = false;
      });
    } catch (_) {
      if (!_current(epoch)) return;
      setState(() {
        _access = null;
        _data = null;
        _busy = false;
        _error = 'Could not open My grade. Refresh your access and try again.';
      });
    }
  }

  Future<void> _open(Future<void> Function() action) async {
    if (!_active || _busy) return;
    final epoch = _epoch;
    String? notice;
    setState(() => _busy = true);
    try {
      await action();
    } catch (_) {
      notice =
          'This action is unavailable. Refresh your grade access and try again.';
    }
    if (_current(epoch)) {
      await _load();
      if (_active && notice != null && _data != null) {
        setState(() => _error = notice);
      }
    }
  }

  Widget _heading(String title) => Padding(
    padding: const EdgeInsets.only(top: 20, bottom: 8),
    child: Text(l(title), style: Theme.of(context).textTheme.titleLarge),
  );
  String _signal(MyGradeSignal signal) => l(switch (signal.source_.value) {
    'attendance' => 'Attendance follow-up',
    'marks' || 'academic' => 'Academic watch',
    'discipline' || 'behaviour' => 'Discipline follow-up',
    'wellbeing' => 'Wellbeing follow-up',
    _ => 'Staff follow-up recommended',
  });

  @override
  Widget build(BuildContext context) {
    final data = _data;
    return ScreenScaffold(
      title: l('My grade'),
      body: !_active || _busy
          ? const Center(child: CircularProgressIndicator())
          : ListView(
              padding: const EdgeInsets.all(16),
              children: [
                if (_error != null)
                  Text(l(_error!), key: const ValueKey('my-grade-error')),
                Align(
                  alignment: Alignment.centerLeft,
                  child: OutlinedButton.icon(
                    onPressed: () => _load(),
                    icon: const Icon(Icons.refresh),
                    label: Text(l('Refresh access')),
                  ),
                ),
                if (_access?.grades.isEmpty == true)
                  Text(l('No current grade-head appointment is available.')),
                if (_access != null && _access!.grades.isNotEmpty)
                  DropdownButtonFormField<int>(
                    initialValue: _grade,
                    isExpanded: true,
                    decoration: InputDecoration(labelText: l('Grade')),
                    items: [
                      for (final grade in _access!.grades)
                        DropdownMenuItem(
                          value: grade.id,
                          child: Text(grade.name),
                        ),
                    ],
                    onChanged: (value) {
                      if (value != null) _load(grade: value);
                    },
                  ),
                if (data != null) ...[
                  Text('${l('Working date')}: ${data.onDate}'),
                  if (!data.writable) Text(l('This view is read-only.')),
                  Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: [
                      if (data.actions.messages && data.writable)
                        FilledButton.icon(
                          onPressed: () =>
                              _open(() => widget.onMessage(data.gradeLevelId)),
                          icon: const Icon(Icons.message_outlined),
                          label: Text(l('Message grade parents')),
                        ),
                      if (data.riskAvailable)
                        OutlinedButton(
                          onPressed: () =>
                              _open(() => widget.onRisk(data.gradeLevelId)),
                          child: Text(l('Early warning and interventions')),
                        ),
                      if (data.awards.available)
                        OutlinedButton(
                          onPressed: () =>
                              _open(() => widget.onAwards(data.gradeLevelId)),
                          child: Text(l('Award nominations')),
                        ),
                    ],
                  ),
                  _heading('Attendance follow-up and academic watch'),
                  if (data.learners.isEmpty)
                    Text(l('No current learners in this grade.')),
                  for (final learner in data.learners)
                    Card(
                      child: Padding(
                        padding: const EdgeInsets.all(12),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              learner.name,
                              style: Theme.of(context).textTheme.titleMedium,
                            ),
                            Text(
                              '${l('Attendance')}: ${l(switch (learner.attendanceState.value) {
                                'present' => 'Present',
                                'absent' => 'Absent',
                                'late' => 'Late',
                                'excused' => 'Excused',
                                'first_half' => 'First half only',
                                'second_half' => 'Second half only',
                                'holiday' => 'School holiday',
                                _ => 'Not yet marked',
                              })}',
                            ),
                            for (final signal in learner.signals)
                              Text(_signal(signal)),
                            if (data.actions.registers && data.writable)
                              TextButton(
                                onPressed: () => _open(
                                  () => widget.onRegister(
                                    learner,
                                    data.gradeLevelId,
                                  ),
                                ),
                                child: Text(l('Open register')),
                              ),
                          ],
                        ),
                      ),
                    ),
                  Wrap(
                    alignment: WrapAlignment.spaceBetween,
                    crossAxisAlignment: WrapCrossAlignment.center,
                    spacing: 8,
                    runSpacing: 8,
                    children: [
                      TextButton(
                        onPressed: data.page > 1
                            ? () => _load(
                                page: data.page - 1,
                                disciplinePage: data.discipline.page,
                              )
                            : null,
                        child: Text(l('Previous page')),
                      ),
                      Text('${data.page} / ${data.lastPage}'),
                      TextButton(
                        onPressed: data.page < data.lastPage
                            ? () => _load(
                                page: data.page + 1,
                                disciplinePage: data.discipline.page,
                              )
                            : null,
                        child: Text(l('Next page')),
                      ),
                    ],
                  ),
                  if (data.discipline.available) ...[
                    _heading('Discipline review queue'),
                    if (data.discipline.items.isEmpty)
                      Text(l('No pending discipline reviews.')),
                    for (final record in data.discipline.items)
                      Card(
                        child: ListTile(
                          title: Text(record.learnerName),
                          subtitle: Text(record.occurredOn),
                          trailing: const Icon(Icons.chevron_right),
                          onTap: () => _open(
                            () =>
                                widget.onDiscipline(record, data.gradeLevelId),
                          ),
                        ),
                      ),
                    Wrap(
                      alignment: WrapAlignment.spaceBetween,
                      crossAxisAlignment: WrapCrossAlignment.center,
                      spacing: 8,
                      runSpacing: 8,
                      children: [
                        TextButton(
                          onPressed: data.discipline.page > 1
                              ? () => _load(
                                  page: data.page,
                                  disciplinePage: data.discipline.page - 1,
                                )
                              : null,
                          child: Text(l('Previous reviews')),
                        ),
                        Text(
                          '${data.discipline.page} / ${data.discipline.lastPage}',
                        ),
                        TextButton(
                          onPressed:
                              data.discipline.page < data.discipline.lastPage
                              ? () => _load(
                                  page: data.page,
                                  disciplinePage: data.discipline.page + 1,
                                )
                              : null,
                          child: Text(l('Next reviews')),
                        ),
                      ],
                    ),
                  ],
                ],
              ],
            ),
    );
  }
}
