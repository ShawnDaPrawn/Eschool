import '../localization/academic_text.dart';
import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'report_batch_screen.dart';
import 'report_comment_screen.dart';
import 'report_design_screen.dart';
import 'report_family_cubit.dart';
import 'report_prepare_screen.dart';
import 'report_repository.dart';
import 'report_results.dart';

class ReportHubScreen extends StatefulWidget {
  const ReportHubScreen({required this.repository, super.key});
  final ReportRepository repository;
  @override
  State<ReportHubScreen> createState() => _ReportHubScreenState();
}

class _ReportHubScreenState extends State<ReportHubScreen> {
  AcademicReportConfigurationData? _configuration;
  List<AcademicReportCommentRow> _comments = [];
  List<AcademicReportBatchSummary> _batches = [];
  int _commentPage = 0,
      _commentLast = 1,
      _batchPage = 0,
      _batchLast = 1,
      _generation = 0;
  bool _busy = false;
  String? _error;
  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _generation++;
    super.dispose();
  }

  Future<void> _load({int? year}) async {
    final generation = ++_generation;
    setState(() {
      _busy = true;
      _error = null;
      _comments = [];
      _batches = [];
    });
    try {
      final config = await widget.repository.configuration(year: year);
      final values = await Future.wait<Object>([
        widget.repository.comments(config.sessionYearId),
        widget.repository.batches(config.sessionYearId),
      ]);
      if (!mounted || generation != _generation) return;
      final comments = values[0] as AcademicReportCommentsData,
          batches = values[1] as AcademicReportBatchesData;
      setState(() {
        _configuration = config;
        _comments = comments.rows;
        _batches = batches.batches;
        _commentPage = comments.page;
        _commentLast = comments.lastPage;
        _batchPage = batches.page;
        _batchLast = batches.lastPage;
      });
    } catch (error) {
      if (mounted && generation == _generation) {
        setState(() {
          _configuration = null;
          _error = reportFailure(error);
        });
      }
    } finally {
      if (mounted && generation == _generation) setState(() => _busy = false);
    }
  }

  Future<void> _more(bool comments) async {
    if (_busy || _configuration == null) return;
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      if (comments) {
        final data = await widget.repository.comments(
          _configuration!.sessionYearId,
          page: _commentPage + 1,
        );
        if (!mounted) return;
        setState(() {
          _comments = [..._comments, ...data.rows];
          _commentPage = data.page;
          _commentLast = data.lastPage;
        });
      } else {
        final data = await widget.repository.batches(
          _configuration!.sessionYearId,
          page: _batchPage + 1,
        );
        if (!mounted) return;
        setState(() {
          _batches = [..._batches, ...data.batches];
          _batchPage = data.page;
          _batchLast = data.lastPage;
        });
      }
    } catch (error) {
      if (mounted) setState(() => _error = reportFailure(error));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _open(Widget page) async {
    await Navigator.of(context).push(AdaptivePageRoute(builder: (_) => page));
    if (mounted) _load(year: _configuration?.sessionYearId);
  }

  @override
  Widget build(BuildContext context) {
    final config = _configuration;
    return AdaptiveScaffold(
      body: ScreenScaffold(
        title: academicText(context, 'Academic report cards'),
        body: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            if (_busy) const LinearProgressIndicator(),
            if (_error != null)
              Text(
                _error!,
                style: TextStyle(color: Theme.of(context).colorScheme.error),
              ),
            TextButton.icon(
              onPressed: _busy
                  ? null
                  : () => _load(year: config?.sessionYearId),
              icon: const Icon(Icons.refresh),
              label: const AcademicText('Refresh reports'),
            ),
            if (config != null) ...[
              DropdownButtonFormField<int>(
                key: ValueKey(config.sessionYearId),
                initialValue: config.sessionYearId,
                isExpanded: true,
                decoration: InputDecoration(
                  labelText: academicText(context, 'School year'),
                ),
                items: [
                  for (final y in config.schoolYears)
                    DropdownMenuItem(value: y.id, child: Text(y.name)),
                ],
                onChanged: _busy ? null : (year) => _load(year: year),
              ),
              Wrap(
                spacing: 12,
                runSpacing: 8,
                children: [
                  if (config.homerooms.isNotEmpty)
                    FilledButton.icon(
                      onPressed: _busy
                          ? null
                          : () => _open(
                              ReportPrepareScreen(
                                repository: widget.repository,
                                configuration: config,
                              ),
                            ),
                      icon: const Icon(Icons.add),
                      label: const AcademicText('Prepare report cards'),
                    ),
                  if (config.canDesign)
                    OutlinedButton(
                      onPressed: _busy
                          ? null
                          : () => _open(
                              ReportDesignScreen(
                                repository: widget.repository,
                                configuration: config,
                              ),
                            ),
                      child: const AcademicText('Designs and comment banks'),
                    ),
                ],
              ),
              const SizedBox(height: 24),
              AcademicText(
                'My report comments',
                style: Theme.of(context).textTheme.headlineSmall,
              ),
              if (_comments.isEmpty && !_busy)
                const AcademicText(
                  'No report comments are currently allocated to you.',
                ),
              for (final row in _comments)
                Card(
                  child: ListTile(
                    title: Text(row.learnerName),
                    subtitle: Text(
                      '${row.periodName} · ${row.scopeLabel}\n${row.text.isEmpty ? 'No comment yet' : row.text}',
                    ),
                    isThreeLine: true,
                    trailing: const Icon(Icons.edit_outlined),
                    onTap: _busy
                        ? null
                        : () => _open(
                            ReportCommentScreen(
                              repository: widget.repository,
                              row: row,
                              year: config.sessionYearId,
                              banks: config.commentBanks,
                            ),
                          ),
                  ),
                ),
              if (_commentPage < _commentLast)
                TextButton(
                  onPressed: _busy ? null : () => _more(true),
                  child: const AcademicText('More allocated comments'),
                ),
              const SizedBox(height: 24),
              AcademicText(
                'Report batches',
                style: Theme.of(context).textTheme.headlineSmall,
              ),
              if (_batches.isEmpty && !_busy)
                const AcademicText(
                  'No report batches are available in your leadership scope.',
                ),
              for (final batch in _batches)
                Card(
                  child: ListTile(
                    title: Text(
                      '${config.homerooms.where((r) => r.id == batch.classSectionId).firstOrNull?.name ?? 'Historical homeroom'} · ${config.terms.where((t) => t.id == batch.termId).firstOrNull?.name ?? 'Annual report'}',
                    ),
                    subtitle: Text(
                      '${reportStatus(batch.state)} · ${batch.learnerCount} learners${batch.mode == 'running' ? ' · Provisional' : ''}',
                    ),
                    trailing: const Icon(Icons.chevron_right),
                    onTap: _busy
                        ? null
                        : () => _open(
                            ReportBatchScreen(
                              repository: widget.repository,
                              batchId: batch.id,
                              banks: config.commentBanks,
                            ),
                          ),
                  ),
                ),
              if (_batchPage < _batchLast)
                TextButton(
                  onPressed: _busy ? null : () => _more(false),
                  child: const AcademicText('Earlier report batches'),
                ),
            ],
          ],
        ),
      ),
      destinations: const [
        AdaptiveDestination(
          icon: Icon(Icons.home_outlined),
          selectedIcon: Icon(Icons.home),
          label: 'Home',
        ),
        AdaptiveDestination(
          icon: Icon(Icons.description_outlined),
          selectedIcon: Icon(Icons.description),
          label: 'Reports',
        ),
      ],
      selectedIndex: 1,
      onDestinationSelected: (index) {
        if (index == 0) Navigator.of(context).pop();
      },
    );
  }
}
