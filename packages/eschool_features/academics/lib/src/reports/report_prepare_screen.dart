import '../localization/academic_text.dart';
import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'report_batch_screen.dart';
import 'report_comment_cubit.dart';
import 'report_family_cubit.dart';
import 'report_repository.dart';
import 'report_results.dart';

class ReportPrepareScreen extends StatefulWidget {
  const ReportPrepareScreen({
    required this.repository,
    required this.configuration,
    super.key,
  });
  final ReportRepository repository;
  final AcademicReportConfigurationData configuration;
  @override
  State<ReportPrepareScreen> createState() => _ReportPrepareScreenState();
}

class _ReportPrepareScreenState extends State<ReportPrepareScreen> {
  int? _room, _template, _term;
  String _mode = 'official';
  AcademicReportRosterData? _roster;
  AcademicReportPreviewData? _preview;
  final Set<int> _selected = {};
  final _keys = ReportOperationKeys();
  bool _busy = false;
  String? _error;
  @override
  void initState() {
    super.initState();
    _room = widget.configuration.homerooms.firstOrNull?.id;
    _template = widget.configuration.templates.firstOrNull?.id;
    _loadRoster();
  }

  List<AcademicReportTerm> get _terms {
    final room = widget.configuration.homerooms
        .where((r) => r.id == _room)
        .firstOrNull;
    return widget.configuration.terms
        .where(
          (t) =>
              room != null &&
              t.gradeFrom <= room.gradeOrder &&
              t.gradeTo >= room.gradeOrder,
        )
        .toList();
  }

  Future<void> _loadRoster() async {
    if (_room == null) return;
    setState(() {
      _busy = true;
      _roster = null;
      _preview = null;
      _selected.clear();
      _error = null;
    });
    try {
      final roster = await widget.repository.roster(
        widget.configuration.sessionYearId,
        _room!,
        _term,
      );
      if (mounted) setState(() => _roster = roster);
    } catch (error) {
      if (mounted) setState(() => _error = reportFailure(error));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  AcademicReportSelection get _selection => AcademicReportSelection(
    templateId: _template!,
    sessionYearId: widget.configuration.sessionYearId,
    termId: _term,
    classSectionId: _room!,
    learnerIds: _selected.toList()..sort(),
    mode: _mode,
  );
  Future<void> _inspect() async {
    if (_selected.isEmpty || _template == null || _busy) return;
    setState(() {
      _busy = true;
      _preview = null;
      _error = null;
    });
    try {
      final preview = await widget.repository.preview(_selection);
      if (mounted) setState(() => _preview = preview);
    } catch (error) {
      if (mounted) setState(() => _error = reportFailure(error));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _prepare() async {
    final preview = _preview;
    if (preview == null || !preview.canPrepare || _busy) return;
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      final body = AcademicReportPrepareWrite(
        templateId: preview.selection.templateId,
        sessionYearId: preview.selection.sessionYearId,
        termId: preview.selection.termId,
        classSectionId: preview.selection.classSectionId,
        learnerIds: preview.selection.learnerIds,
        mode: preview.selection.mode,
        fingerprint: preview.fingerprint,
      );
      final draft = await widget.repository.prepare(
        body,
        _keys.key('prepare', body.toJson()),
      );
      _keys.completed('prepare', body.toJson());
      if (!mounted) return;
      Navigator.of(context).pushReplacement(
        AdaptivePageRoute(
          builder: (_) => ReportBatchScreen(
            repository: widget.repository,
            batchId: draft.id,
            banks: widget.configuration.commentBanks,
          ),
        ),
      );
    } catch (error) {
      if (mounted) setState(() => _error = reportFailure(error));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) => ScreenScaffold(
    title: academicText(context, 'Prepare report cards'),
    body: ListView(
      padding: const EdgeInsets.all(16),
      children: [
        if (_busy) const LinearProgressIndicator(),
        if (_error != null)
          Text(
            _error!,
            style: TextStyle(color: Theme.of(context).colorScheme.error),
          ),
        const AcademicText(
          'Choose a reporting period and learners, then preview current evidence. Fees never restrict a report.',
        ),
        DropdownButtonFormField<int>(
          initialValue: _template,
          isExpanded: true,
          decoration: InputDecoration(
            labelText: academicText(context, 'Report design'),
          ),
          items: [
            for (final t in widget.configuration.templates)
              DropdownMenuItem(value: t.id, child: Text(t.name)),
          ],
          onChanged: _busy
              ? null
              : (id) => setState(() {
                  _template = id;
                  _preview = null;
                }),
        ),
        DropdownButtonFormField<int>(
          initialValue: _room,
          isExpanded: true,
          decoration: InputDecoration(
            labelText: academicText(context, 'Homeroom'),
          ),
          items: [
            for (final r in widget.configuration.homerooms)
              DropdownMenuItem(value: r.id, child: Text(r.name)),
          ],
          onChanged: _busy
              ? null
              : (id) {
                  _room = id;
                  _term = null;
                  _loadRoster();
                },
        ),
        DropdownButtonFormField<int>(
          key: ValueKey('term:$_room:$_term'),
          initialValue: _term ?? 0,
          isExpanded: true,
          decoration: InputDecoration(
            labelText: academicText(context, 'Reporting period'),
          ),
          items: [
            const DropdownMenuItem(
              value: 0,
              child: AcademicText('Annual report'),
            ),
            for (final t in _terms)
              DropdownMenuItem(value: t.id, child: Text(t.name)),
          ],
          onChanged: _busy
              ? null
              : (id) {
                  _term = id == 0 ? null : id;
                  _loadRoster();
                },
        ),
        DropdownButtonFormField<String>(
          initialValue: _mode,
          isExpanded: true,
          decoration: InputDecoration(
            labelText: academicText(context, 'Report type'),
          ),
          items: const [
            DropdownMenuItem(
              value: 'official',
              child: AcademicText('Official completed results'),
            ),
            DropdownMenuItem(
              value: 'running',
              child: AcademicText('Provisional running report'),
            ),
          ],
          onChanged: _busy
              ? null
              : (mode) => setState(() {
                  _mode = mode!;
                  _preview = null;
                }),
        ),
        const SizedBox(height: 16),
        if (_template == null)
          const AcademicText('Create a report design first.'),
        if (_roster != null) ...[
          Text(
            '${_selected.length} of ${_roster!.learners.length} learners selected',
            style: Theme.of(context).textTheme.titleMedium,
          ),
          TextButton(
            onPressed: _busy
                ? null
                : () => setState(() {
                    _selected.length == _roster!.learners.length
                        ? _selected.clear()
                        : _selected.addAll(_roster!.learners.map((l) => l.id));
                    _preview = null;
                  }),
            child: Text(
              _selected.length == _roster!.learners.length
                  ? 'Clear selection'
                  : 'Select all learners',
            ),
          ),
          for (final learner in _roster!.learners)
            CheckboxListTile(
              title: Text(learner.name),
              value: _selected.contains(learner.id),
              onChanged: _busy
                  ? null
                  : (checked) => setState(() {
                      checked == true
                          ? _selected.add(learner.id)
                          : _selected.remove(learner.id);
                      _preview = null;
                    }),
            ),
        ],
        FilledButton(
          onPressed: _busy || _selected.isEmpty || _template == null
              ? null
              : _inspect,
          child: const AcademicText('Preview selected reports'),
        ),
        if (_preview != null) ...[
          const SizedBox(height: 24),
          AcademicText(
            'Evidence preview',
            style: Theme.of(context).textTheme.headlineSmall,
          ),
          if (!_preview!.canPrepare)
            const AcademicText(
              'Some selected learners need published plans or completed results. Adjust the selection, complete their results, or explicitly choose a provisional report.',
            ),
          for (final payload in _preview!.cards)
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: ReportResults(payload: payload),
              ),
            ),
          FilledButton(
            onPressed: _busy || !_preview!.canPrepare ? null : _prepare,
            child: const AcademicText('Prepare reviewed reports'),
          ),
        ],
      ],
    ),
  );
}
