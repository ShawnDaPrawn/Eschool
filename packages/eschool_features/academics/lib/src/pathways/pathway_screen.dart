import 'dart:async';

import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../localization/academic_text.dart';
import 'pathway_repository.dart';

/// Calendar sources and explicitly hypothetical calculations, never mark writes.
class PathwayScreen extends StatefulWidget {
  const PathwayScreen({
    required this.repository,
    this.initialLearnerId,
    super.key,
  });
  final PathwayRepository repository;
  final int? initialLearnerId;
  @override
  State<PathwayScreen> createState() => _PathwayScreenState();
}

class _PathwayScreenState extends State<PathwayScreen>
    with WidgetsBindingObserver {
  PathwayConfiguration? _configuration;
  final _references = <PathwayReference>[];
  final _marks = <int, TextEditingController>{};
  final _subjects = <int>{};
  PathwayReference? _reference;
  PathwayProjection? _projection;
  int? _learner, _year, _term, _next;
  int _epoch = 0;
  bool _busy = false, _foreground = true, _retired = false;
  String _mode = 'scenario', _error = '';
  String l(String key) => academicText(context, key);
  bool get _active =>
      mounted && _foreground && !_retired && widget.repository.isActive;
  bool _current(int epoch) => _active && epoch == _epoch;

  @override
  void initState() {
    super.initState();
    _learner = widget.initialLearnerId;
    WidgetsBinding.instance.addObserver(this);
    unawaited(_reload());
  }

  void _eraseCalculation() {
    for (final value in _marks.values) {
      value.clear();
      value.dispose();
    }
    _marks.clear();
    _subjects.clear();
    _projection = null;
    _year = _term = null;
  }

  void _erase() {
    _eraseCalculation();
    _configuration = null;
    _references.clear();
    _reference = null;
    _next = null;
    _error = '';
  }

  @override
  void didUpdateWidget(PathwayScreen oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (!identical(oldWidget.repository, widget.repository)) {
      _epoch++;
      _erase();
      _learner = widget.initialLearnerId;
      _retired = false;
      _busy = false;
      if (_foreground) unawaited(_reload());
    }
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state != AppLifecycleState.resumed) {
      _epoch++;
      setState(() {
        _foreground = false;
        _busy = false;
        _erase();
      });
    } else if (!_foreground) {
      setState(() => _foreground = true);
      unawaited(_reload());
    }
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    _epoch++;
    _erase();
    super.dispose();
  }

  Future<void> _run(Future<void> Function(int) action) async {
    if (!_active || _busy) return;
    final epoch = ++_epoch;
    setState(() {
      _busy = true;
      _error = '';
    });
    try {
      await action(epoch);
    } catch (error) {
      if (!mounted || epoch != _epoch || !_foreground) return;
      setState(() {
        if (!widget.repository.isActive ||
            error is ApiException &&
                [401, 403, 404].contains(error.statusCode)) {
          _erase();
          _retired = true;
        } else if (error is ApiException && error.statusCode == 409) {
          _eraseCalculation();
          _reference = null;
        }
        _error = error is ApiException && error.isApiMessage
            ? error.errorMessage
            : error is FormatException
            ? error.message
            : 'The pathway calculation could not be loaded. Try again.';
      });
    } finally {
      if (mounted && epoch == _epoch) setState(() => _busy = false);
    }
  }

  Future<void> _reload() => _run((epoch) async {
    setState(_erase);
    final config = await widget.repository.configuration();
    if (!_current(epoch)) return;
    final refs = await widget.repository.references();
    if (!_current(epoch)) return;
    setState(() {
      _configuration = config;
      _learner = config.children.any((c) => c.id == _learner)
          ? _learner
          : config.children.firstOrNull?.id;
      _references.addAll(refs.items);
      _next = refs.nextPage;
    });
  });
  Future<void> _more() => _run((epoch) async {
    final page = _next;
    if (page == null) return;
    final refs = await widget.repository.references(page: page);
    if (!_current(epoch)) return;
    setState(() {
      for (final row in refs.items) {
        if (!_references.any((r) => r.id == row.id)) _references.add(row);
      }
      _next = refs.nextPage;
    });
  });
  Future<void> _open(PathwayReference row) => _run((epoch) async {
    setState(() {
      _eraseCalculation();
      _reference = null;
    });
    final config = await widget.repository.configuration();
    if (!_current(epoch)) return;
    if (!config.children.any((c) => c.id == _learner)) {
      throw ApiException(
        'Your pathway access changed. Reopen this screen.',
        statusCode: 403,
        isApiMessage: true,
      );
    }
    final fresh = await widget.repository.read(row.id);
    if (!_current(epoch)) return;
    setState(() {
      _configuration = config;
      _reference = fresh;
      _mode = 'scenario';
    });
  });
  String _name(int id) =>
      _configuration!.subjects.where((s) => s.id == id).firstOrNull?.name ??
      l('Subject');
  String _percent(int value) =>
      '${value ~/ 100}.${(value % 100).toString().padLeft(2, '0')}%';
  int? _parse(String value) {
    final text = value.trim().replaceAll(',', '.');
    if (text.isEmpty) return null;
    final match = RegExp(r'^(\d{1,3})(?:\.(\d{1,2}))?$').firstMatch(text);
    if (match == null)
      throw const FormatException(
        'Use percentages from 0 to 100 with at most two decimal places.',
      );
    final number =
        int.parse(match[1]!) * 100 +
        int.parse((match[2] ?? '').padRight(2, '0'));
    if (number > 10000)
      throw const FormatException(
        'Use percentages from 0 to 100 with at most two decimal places.',
      );
    return number;
  }

  Future<void> _calculate() => _run((epoch) async {
    final row = _reference, learner = _learner;
    if (row == null || learner == null) return;
    final guide = row.kind.value == 'subject_choice_guide';
    if (!guide && _mode == 'released' && _year == null) {
      throw const FormatException('Choose a school year for released marks.');
    }
    final input = PathwayProjectionRequest.fromJson({
      'learner_id': learner,
      'revision': row.revision,
      'source': guide ? 'selection' : _mode,
      if (guide) 'subject_ids': _subjects.toList(),
      if (!guide && _mode == 'scenario')
        'marks': [
          for (final rule in row.rules.subjects!)
            if (rule.countsForScore || rule.minimumBp != null)
              {
                'subject_id': rule.subjectId,
                'percentage_bp': _parse(_marks[rule.subjectId]?.text ?? ''),
              },
        ],
      if (!guide && _mode == 'released') ...{
        'session_year_id': _year,
        'term_id': _term,
      },
    });
    final result = await widget.repository.project(row, input);
    if (!_current(epoch)) return;
    setState(() => _projection = result);
  });
  Widget _select(
    int? value,
    String title,
    List<DropdownMenuItem<int>> items,
    ValueChanged<int?> change,
  ) => DropdownButtonFormField<int>(
    key: ValueKey((title, value)),
    initialValue: value,
    isExpanded: true,
    decoration: InputDecoration(labelText: l(title)),
    items: items,
    onChanged: _busy ? null : change,
  );
  Widget _guide(PathwayReference row) => Column(
    crossAxisAlignment: CrossAxisAlignment.stretch,
    children: [
      Text(
        l('Compulsory subjects'),
        style: Theme.of(context).textTheme.titleMedium,
      ),
      for (final id in row.rules.compulsorySubjectIds!) _choice(id),
      for (final group in row.rules.electiveGroups!) ...[
        Text(
          '${group.title} · ${l('Number of subjects to choose')}: ${group.choose}',
          style: Theme.of(context).textTheme.titleMedium,
        ),
        for (final id in group.subjectIds) _choice(id),
      ],
    ],
  );
  Widget _choice(int id) => CheckboxListTile(
    key: ValueKey('pathway-choice-$id'),
    contentPadding: EdgeInsets.zero,
    title: Text(_name(id)),
    value: _subjects.contains(id),
    onChanged: _busy
        ? null
        : (value) => setState(() {
            if (value == true) {
              _subjects.add(id);
            } else {
              _subjects.remove(id);
            }
            _projection = null;
          }),
  );
  Widget _programme(PathwayReference row) => Column(
    crossAxisAlignment: CrossAxisAlignment.stretch,
    children: [
      SegmentedButton<String>(
        segments: [
          ButtonSegment(
            value: 'scenario',
            label: Text(l('Hypothetical marks')),
          ),
          ButtonSegment(
            value: 'released',
            label: Text(l('Released school marks')),
          ),
        ],
        selected: {_mode},
        onSelectionChanged: _busy
            ? null
            : (value) => setState(() {
                _eraseCalculation();
                _mode = value.single;
              }),
      ),
      const SizedBox(height: 16),
      if (_mode == 'released') ...[
        _select(
          _year,
          'School year',
          [
            for (final year in _configuration!.years)
              DropdownMenuItem(value: year.id, child: Text(year.name)),
          ],
          (value) => setState(() {
            _year = value;
            _term = null;
            _projection = null;
          }),
        ),
        if (_year != null)
          _select(
            _term,
            'Released period',
            [
              DropdownMenuItem<int>(
                value: null,
                child: Text(l('Whole school year')),
              ),
              for (final term in _configuration!.terms.where(
                (t) => t.sessionYearId == _year,
              ))
                DropdownMenuItem(value: term.id, child: Text(term.name)),
            ],
            (value) => setState(() {
              _term = value;
              _projection = null;
            }),
          ),
      ] else ...[
        Text(
          l(
            'These hypothetical percentages are not saved or applied to school marks. Leave missing marks blank.',
          ),
        ),
        for (final rule in row.rules.subjects!)
          if (rule.countsForScore || rule.minimumBp != null)
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 8),
              child: TextField(
                key: ValueKey('pathway-mark-${rule.subjectId}'),
                controller: _marks.putIfAbsent(
                  rule.subjectId,
                  () => TextEditingController(),
                ),
                enabled: !_busy,
                keyboardType: const TextInputType.numberWithOptions(
                  decimal: true,
                ),
                maxLength: 6,
                decoration: InputDecoration(
                  labelText: '${_name(rule.subjectId)} (%)',
                ),
                onChanged: (_) => setState(() => _projection = null),
              ),
            ),
      ],
      const SizedBox(height: 12),
      Text(
        '${l('Minimum points stated by the source')}: ${row.rules.minimumScore}',
      ),
      Text('${l('Number of best subjects to count')}: ${row.rules.bestN}'),
      for (final rule in row.rules.subjects!)
        Text(
          '${_name(rule.subjectId)} · ${rule.countsForScore ? l('Counts toward the score') : l('Excluded from the score')}${rule.minimumBp == null ? '' : ' · ${l('Minimum percentage')}: ${_percent(rule.minimumBp!)}'}',
        ),
      for (final band in row.rules.bands!)
        Text('${_percent(band.minimumBp)} · ${l('Points')}: ${band.points}'),
    ],
  );
  Widget _result(PathwayProjection result) => Card(
    child: Padding(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          if (result.score case final score?) ...[
            Text(
              '${l('Projected admission score')}: ${score.projectedScore ?? l('Unknown')}',
              style: Theme.of(context).textTheme.titleLarge,
            ),
            Text(
              l(switch (score.status.value) {
                'meets_published_minimum' =>
                  'Meets the entered score and subject minimums',
                'below_published_minimum' =>
                  'Below an entered score or subject minimum',
                _ => 'Missing marks prevent a complete projection',
              }),
            ),
            if (score.projectedScore == null)
              Text('${l('Known subject subtotal')}: ${score.knownSubtotal}'),
            for (final item in score.subjects)
              Text(
                '${_name(item.subjectId)}: ${item.percentageBp == null ? l('Unknown') : _percent(item.percentageBp!)}',
              ),
            for (final evidence in result.releasedEvidence)
              Text(
                '${_name(evidence.subjectId)} · ${evidence.gradeName} · ${l('Assessed share')}: ${_percent(evidence.coverageBp)}${evidence.provisional ? ' · ${l('Provisional')}' : ''}',
              ),
          ],
          if (result.selection case final selection?) ...[
            Text(
              l(
                selection.valid
                    ? 'Meets the entered subject-choice rules'
                    : 'Review these subject choices',
              ),
              style: Theme.of(context).textTheme.titleLarge,
            ),
            for (final id in selection.missingCompulsorySubjectIds)
              Text('${l('Missing compulsory subject')}: ${_name(id)}'),
            for (final group in selection.groups)
              Text('${group.title}: ${group.selected} / ${group.choose}'),
          ],
          Text(
            l(
              'This is planning guidance, not an admission offer. Confirm all programme conditions with the institution.',
            ),
          ),
        ],
      ),
    ),
  );
  @override
  Widget build(BuildContext context) => ScreenScaffold(
    title: l('Subject choice and pathways'),
    body: !_foreground || _retired || !widget.repository.isActive
        ? Center(
            child: Text(
              l(
                _error.isNotEmpty
                    ? _error
                    : 'Your pathway access changed. Reopen this screen.',
              ),
            ),
          )
        : SingleChildScrollView(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                if (_busy) const LinearProgressIndicator(),
                if (_error.isNotEmpty)
                  Padding(
                    padding: const EdgeInsets.symmetric(vertical: 12),
                    child: Text(
                      l(_error),
                      key: const ValueKey('pathway-error'),
                    ),
                  ),
                OutlinedButton(
                  onPressed: _busy ? null : _reload,
                  child: Text(l('Refresh')),
                ),
                if (_configuration case final config?) ...[
                  if (config.children.isEmpty)
                    Text(
                      l(
                        'No approved current learner is available for this planner.',
                      ),
                    )
                  else
                    _select(
                      _learner,
                      'Learner',
                      [
                        for (final child in config.children)
                          DropdownMenuItem(
                            value: child.id,
                            child: Text(child.name),
                          ),
                      ],
                      (value) => setState(() {
                        _eraseCalculation();
                        _learner = value;
                      }),
                    ),
                  if (_reference case final row?) ...[
                    OutlinedButton(
                      onPressed: _busy
                          ? null
                          : () => setState(() {
                              _eraseCalculation();
                              _reference = null;
                            }),
                      child: Text(l('All pathway sources')),
                    ),
                    Text(
                      row.title,
                      style: Theme.of(context).textTheme.headlineSmall,
                    ),
                    if (row.institution != null) Text(row.institution!),
                    Text(
                      '${l('Source year')}: ${row.sourceYear} · ${l('Revision')}: ${row.revision}',
                    ),
                    Text(
                      '${l('Reviewed')}: ${row.reviewedOn} · ${l('Review expires')}: ${row.expiresOn}',
                    ),
                    TextButton.icon(
                      onPressed: () => launchUrl(
                        Uri.parse(row.sourceUrl),
                        mode: LaunchMode.externalApplication,
                      ),
                      icon: const Icon(Icons.open_in_new),
                      label: Text(row.sourceTitle),
                    ),
                    Text(row.guidance),
                    const SizedBox(height: 16),
                    if (row.kind.value == 'subject_choice_guide')
                      _guide(row)
                    else
                      _programme(row),
                    const SizedBox(height: 16),
                    FilledButton(
                      key: const ValueKey('pathway-calculate'),
                      onPressed: _busy || _learner == null ? null : _calculate,
                      child: Text(
                        l(
                          row.kind.value == 'subject_choice_guide'
                              ? 'Check subject choices'
                              : 'Calculate projection',
                        ),
                      ),
                    ),
                    if (_projection case final result?) _result(result),
                  ] else ...[
                    if (_references.isEmpty && !_busy)
                      Text(
                        l(
                          'No reviewed pathway sources are published yet. Ask the school about its current guidance.',
                        ),
                      ),
                    for (final row in _references)
                      Card(
                        child: ListTile(
                          title: Text(row.title),
                          subtitle: Text(row.sourceTitle),
                          trailing: const Icon(Icons.chevron_right),
                          onTap: _busy || _learner == null
                              ? null
                              : () => _open(row),
                        ),
                      ),
                    if (_next != null)
                      OutlinedButton(
                        onPressed: _busy ? null : _more,
                        child: Text(l('More pathway sources')),
                      ),
                  ],
                ],
              ],
            ),
          ),
  );
}
