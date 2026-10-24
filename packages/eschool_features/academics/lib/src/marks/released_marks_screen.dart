import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'academic_labels.dart';
import 'mark_input.dart';
import 'marks_repository.dart';

class ReleasedMarksScreen extends StatefulWidget {
  const ReleasedMarksScreen({
    required this.repository,
    required this.learner,
    required this.learnerName,
    super.key,
  });
  final MarksRepository repository;
  final int learner;
  final String learnerName;
  @override
  State<ReleasedMarksScreen> createState() => _ReleasedMarksScreenState();
}

class _ReleasedMarksScreenState extends State<ReleasedMarksScreen> {
  List<AcademicReleasePeriod>? _periods;
  AcademicReleasePeriod? _period;
  AcademicReleasedData? _results;
  bool _busy = true;
  String? _error;
  int _generation = 0;
  String l(String key) => academicLabel(context, key);
  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void didUpdateWidget(covariant ReleasedMarksScreen old) {
    super.didUpdateWidget(old);
    if (old.learner != widget.learner || old.repository != widget.repository) {
      _generation++;
      _periods = null;
      _period = null;
      _results = null;
      _load();
    }
  }

  Future<void> _load() async {
    final generation = ++_generation;
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      final periods = await widget.repository.periods(widget.learner);
      if (!mounted || generation != _generation) return;
      setState(() {
        _periods = periods.periods;
        _period = periods.periods.firstOrNull;
        _results = null;
        _busy = false;
      });
      if (_period != null) await _select(_period!);
    } catch (e) {
      if (mounted && generation == _generation) {
        setState(() {
          _busy = false;
          _error = e is ApiException && e.isApiMessage
              ? e.errorMessage
              : l('loadFailed');
        });
      }
    }
  }

  Future<void> _select(AcademicReleasePeriod period) async {
    final generation = ++_generation;
    setState(() {
      _period = period;
      _results = null;
      _busy = true;
      _error = null;
    });
    try {
      final results = await widget.repository.released(
        widget.learner,
        period.sessionYearId,
        period.termId,
      );
      if (mounted && generation == _generation) {
        setState(() {
          _results = results;
          _busy = false;
        });
      }
    } catch (e) {
      if (mounted && generation == _generation) {
        setState(() {
          _busy = false;
          _error = e is ApiException && e.isApiMessage
              ? e.errorMessage
              : l('loadFailed');
        });
      }
    }
  }

  @override
  void dispose() {
    _generation++;
    super.dispose();
  }

  @override
  Widget build(BuildContext context) => ScreenScaffold(
    title: '${widget.learnerName} · ${l('marks')}',
    body: Column(
      children: [
        if (_periods?.isNotEmpty == true)
          Padding(
            padding: const EdgeInsets.all(16),
            child: DropdownButtonFormField<AcademicReleasePeriod>(
              initialValue: _period,
              isExpanded: true,
              items: _periods!
                  .map(
                    (p) => DropdownMenuItem(
                      value: p,
                      child: Text(
                        '${p.yearName} · ${p.termName ?? l('yearResults')}',
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                  )
                  .toList(),
              onChanged: _busy
                  ? null
                  : (period) {
                      if (period != null) _select(period);
                    },
            ),
          ),
        if (_busy)
          const Expanded(child: Center(child: CircularProgressIndicator()))
        else if (_error != null)
          Expanded(
            child: Center(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(_error!),
                  TextButton(onPressed: _load, child: Text(l('retry'))),
                ],
              ),
            ),
          )
        else
          Expanded(
            child: _results == null || _results!.results.isEmpty
                ? Center(child: Text(l('noReleased')))
                : ListView(
                    padding: const EdgeInsets.all(16),
                    children: [
                      for (final result in _results!.results)
                        Card(
                          child: Padding(
                            padding: const EdgeInsets.all(16),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  result.subjectName ?? l('subject'),
                                  style: Theme.of(
                                    context,
                                  ).textTheme.titleMedium,
                                ),
                                if (result.gradeName != null)
                                  Text(result.gradeName!),
                                const SizedBox(height: 8),
                                Text(
                                  '${result.percentageBp == null ? '—' : '${markDecimal(result.percentageBp!)}%'} · ${l('rating')}: ${result.ratingLevel ?? '—'}',
                                ),
                                Text(
                                  '${l('coverage')}: ${markDecimal(result.coverageBp)}%',
                                ),
                                if (result.provisional)
                                  Chip(label: Text(l('provisional'))),
                              ],
                            ),
                          ),
                        ),
                    ],
                  ),
          ),
      ],
    ),
  );
}
