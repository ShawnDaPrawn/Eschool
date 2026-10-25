import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import '../localization/academic_text.dart';
import 'progress_repository.dart';

/// This reader never requests staff notes, caseloads or current unapproved marks.
class FamilyProgressScreen extends StatefulWidget {
  const FamilyProgressScreen({
    required this.repository,
    required this.learnerId,
    required this.canRead,
    super.key,
  });
  final ProgressRepository repository;
  final int learnerId;
  final bool Function() canRead;
  @override
  State<FamilyProgressScreen> createState() => _FamilyProgressScreenState();
}

class _FamilyProgressScreenState extends State<FamilyProgressScreen>
    with WidgetsBindingObserver {
  List<AcademicReleasePeriod> _periods = [];
  List<ProgressReleasedBand> _results = [];
  int? _period;
  int _epoch = 0;
  bool _busy = true, _suspended = false;
  String _error = '';
  bool get _active =>
      mounted && !_suspended && widget.canRead() && widget.repository.isActive;
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _load();
  }

  @override
  void dispose() {
    _epoch++;
    _results = [];
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  void didUpdateWidget(covariant FamilyProgressScreen oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.learnerId != widget.learnerId ||
        oldWidget.repository != widget.repository) {
      _load();
    }
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state != AppLifecycleState.resumed) {
      _epoch++;
      if (mounted) {
        setState(() {
          _suspended = true;
          _results = [];
          _periods = [];
        });
      }
    } else if (mounted) {
      setState(() => _suspended = false);
      _load();
    }
  }

  Future<void> _load() async {
    final epoch = ++_epoch;
    setState(() {
      _busy = true;
      _periods = [];
      _results = [];
      _period = null;
      _error = '';
    });
    try {
      final r = await widget.repository.periods(widget.learnerId);
      if (!_active || epoch != _epoch) return;
      setState(() {
        _periods = r.periods;
        _busy = false;
      });
    } catch (_) {
      if (_active && epoch == _epoch) {
        setState(() {
          _busy = false;
          _error =
              'Released progress is unavailable. Check your access and retry.';
        });
      }
    }
  }

  Future<void> _select(int? index) async {
    if (index == null) return;
    final epoch = ++_epoch, period = _periods[index];
    setState(() {
      _period = index;
      _busy = true;
      _results = [];
      _error = '';
    });
    try {
      final r = await widget.repository.familyBands(
        widget.learnerId,
        period.sessionYearId,
        period.termId,
      );
      if (_active && epoch == _epoch) {
        setState(() {
          _results = r.results;
          _busy = false;
        });
      }
    } catch (_) {
      if (_active && epoch == _epoch) {
        setState(() {
          _busy = false;
          _results = [];
          _error =
              'Released progress is unavailable. Check your access and retry.';
        });
      }
    }
  }

  String _band(ProgressReleasedBand r) => switch (r.band) {
    ProgressReleasedBandBandEnum.high => 'Strong progress',
    ProgressReleasedBandBandEnum.secure => 'On track',
    ProgressReleasedBandBandEnum.support => 'Extra practice',
    _ => 'Insufficient evidence',
  };
  @override
  Widget build(BuildContext context) => ScreenScaffold(
    title: academicText(context, 'Learning progress'),
    body: !_active
        ? Center(child: AcademicText('Reopen progress to check your access.'))
        : Center(
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 760),
              child: ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  AcademicText(
                    'Progress uses released assessment evidence. Missing evidence is not a zero mark.',
                  ),
                  if (_busy) const LinearProgressIndicator(),
                  if (_error.isNotEmpty) ...[
                    AcademicText(_error),
                    TextButton(onPressed: _load, child: AcademicText('Retry')),
                  ],
                  if (_periods.isNotEmpty)
                    DropdownButtonFormField<int>(
                      key: ValueKey('period-${widget.learnerId}'),
                      initialValue: _period,
                      isExpanded: true,
                      decoration: InputDecoration(
                        labelText: academicText(context, 'Released period'),
                      ),
                      items: [
                        for (var i = 0; i < _periods.length; i++)
                          DropdownMenuItem(
                            value: i,
                            child: Text(
                              '${_periods[i].yearName} · ${_periods[i].termName ?? academicText(context, 'Full year')}',
                            ),
                          ),
                      ],
                      onChanged: _busy ? null : _select,
                    ),
                  if (!_busy && _periods.isEmpty && _error.isEmpty)
                    AcademicText('No released progress is available yet.'),
                  if (!_busy &&
                      _period != null &&
                      _results.isEmpty &&
                      _error.isEmpty)
                    AcademicText(
                      'No released subject progress is available for this period.',
                    ),
                  ..._results.map(
                    (r) => Card(
                      child: Padding(
                        padding: const EdgeInsets.all(16),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              r.subjectName,
                              style: Theme.of(context).textTheme.titleMedium,
                            ),
                            const SizedBox(height: 8),
                            AcademicText(_band(r)),
                            if (r.percentageBp != null)
                              Text(
                                '${(r.percentageBp! / 100).toStringAsFixed(2)}%',
                              ),
                            Text(
                              '${academicText(context, 'Evidence coverage')}: ${(r.coverageBp / 100).toStringAsFixed(2)}%',
                            ),
                            if (r.provisional)
                              AcademicText(
                                'Running progress; the official result is shown in released marks.',
                              ),
                          ],
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
  );
}
