import '../localization/academic_text.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'report_family_cubit.dart';
import 'report_pdf_screen.dart';
import 'report_repository.dart';

class FamilyReportsScreen extends StatefulWidget {
  const FamilyReportsScreen({
    required this.repository,
    required this.learner,
    required this.learnerName,
    super.key,
  });
  final ReportRepository repository;
  final int learner;
  final String learnerName;
  @override
  State<FamilyReportsScreen> createState() => _FamilyReportsScreenState();
}

class _FamilyReportsScreenState extends State<FamilyReportsScreen> {
  late FamilyReportsCubit _cubit;
  void _create() {
    _cubit = FamilyReportsCubit(widget.repository, widget.learner)..load();
  }

  @override
  void initState() {
    super.initState();
    _create();
  }

  @override
  void didUpdateWidget(covariant FamilyReportsScreen old) {
    super.didUpdateWidget(old);
    if (old.learner != widget.learner ||
        old.repository.schoolId != widget.repository.schoolId) {
      _cubit.close();
      _create();
    }
  }

  @override
  void dispose() {
    _cubit.close();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) => ScreenScaffold(
    title: '${widget.learnerName} · Report cards',
    body: BlocBuilder<FamilyReportsCubit, FamilyReportsState>(
      bloc: _cubit,
      builder: (context, state) => RefreshIndicator(
        onRefresh: () => _cubit.load(),
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            if (state.busy) const LinearProgressIndicator(),
            if (state.error != null)
              Text(
                state.error!,
                style: TextStyle(color: Theme.of(context).colorScheme.error),
              ),
            TextButton.icon(
              onPressed: state.busy ? null : () => _cubit.load(),
              icon: const Icon(Icons.refresh),
              label: const AcademicText('Refresh reports'),
            ),
            if (state.reports.isEmpty && !state.busy)
              const AcademicText('No released report cards are available yet.'),
            for (final report in state.reports)
              Card(
                child: ListTile(
                  leading: const Icon(Icons.description_outlined),
                  title: Text(
                    '${report.yearName} · ${report.termName ?? 'Annual report'}',
                  ),
                  subtitle: Text(
                    '${report.provisional ? 'Provisional · ' : ''}${report.readAt == null ? 'Unread · ' : ''}Released ${report.releasedAt.substring(0, 10)}',
                  ),
                  trailing: const Icon(Icons.chevron_right),
                  onTap: state.busy
                      ? null
                      : () async {
                          await Navigator.of(context).push(
                            AdaptivePageRoute(
                              builder: (_) => ReportPdfScreen(
                                repository: widget.repository,
                                card: report.id,
                                learner: widget.learner,
                                title:
                                    '${report.yearName} · ${report.termName ?? 'Annual report'}',
                              ),
                            ),
                          );
                          if (mounted) _cubit.load();
                        },
                ),
              ),
            if (state.page < state.lastPage)
              OutlinedButton(
                onPressed: state.busy ? null : () => _cubit.load(more: true),
                child: const AcademicText('Earlier reports'),
              ),
          ],
        ),
      ),
    ),
  );
}
