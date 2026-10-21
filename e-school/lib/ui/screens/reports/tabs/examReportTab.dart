import 'package:eschool/app/routes.dart';
import 'package:eschool/cubits/appConfigurationCubit.dart';
import 'package:eschool/cubits/resultsCubit.dart';
import 'package:eschool/cubits/resultsOnlineCubit.dart';
import 'package:eschool/cubits/studentDashboardCubit.dart';
import 'package:eschool/cubits/authCubit.dart';
import 'package:eschool/cubits/sessionYearCubit.dart';
import 'package:eschool/data/models/result.dart';
import 'package:eschool/data/models/resultOnline.dart';
import 'package:eschool/data/models/sessionYear.dart';
import 'package:eschool/data/models/subject.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool/ui/screens/leave/widgets/dropdownButtonContainer.dart';
import 'package:eschool/ui/screens/reports/cubits/examReportCubit.dart';

import 'package:eschool/ui/screens/reports/widgets/sessionYearPickerBottomsheetContainer.dart';
import 'package:eschool/ui/screens/reports/widgets/subjectPickerBottomsheetContainer.dart';
import 'package:eschool/ui/widgets/bottomSheetTopBarMenu.dart';
import 'package:eschool/ui/widgets/customShimmerContainer.dart';
import 'package:eschool/ui/widgets/errorContainer.dart';
import 'package:eschool/ui/widgets/listItemForExamAndResult.dart';
import 'package:eschool/ui/widgets/listItemForOnlineExamAndOnlineResult.dart';
import 'package:eschool/ui/widgets/shimmerLoadingContainer.dart';
import 'package:eschool/utils/labelKeys.dart';
import 'package:eschool/utils/uiUtils.dart';
import 'package:fl_chart/fl_chart.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

enum ExamType { offline, online }

class ExamReportTab extends StatefulWidget {
  final int? childId;
  final List<Subject>? subjects;
  const ExamReportTab({super.key, this.childId, this.subjects});

  @override
  State<ExamReportTab> createState() => _ExamReportTabState();

  static Widget buildShimmer(BuildContext context, double topPadding) {
    return SingleChildScrollView(
      padding: EdgeInsets.only(top: topPadding, bottom: 20),
      child: Column(
        children: [
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 20),
            child: Row(
              children: [
                Expanded(
                  child: ShimmerLoadingContainer(
                    child: CustomShimmerContainer(height: 48, borderRadius: 8),
                  ),
                ),
                const SizedBox(width: 15),
                Expanded(
                  child: ShimmerLoadingContainer(
                    child: CustomShimmerContainer(height: 48, borderRadius: 8),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 15),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 20),
            child: ShimmerLoadingContainer(
              child: CustomShimmerContainer(height: 120, borderRadius: 10),
            ),
          ),
          const SizedBox(height: 15),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 20),
            child: ShimmerLoadingContainer(
              child: CustomShimmerContainer(height: 200, borderRadius: 10),
            ),
          ),
        ],
      ),
    );
  }
}

class _ExamReportTabState extends State<ExamReportTab>
    with AutomaticKeepAliveClientMixin {
  SessionYear? _selectedSessionYear;
  ExamType _selectedExamType = ExamType.offline;

  // Online subject filter
  Subject? _selectedSubject; // null = All Subjects

  @override
  bool get wantKeepAlive => true;

  @override
  void initState() {
    super.initState();
    Future.delayed(Duration.zero, _initializeSessionYear);
  }

  // ── Initialisation ──────────────────────────────────────────────────────

  void _initializeSessionYear() {
    final sessionYearState = context.read<SessionYearCubit>().state;
    if (sessionYearState is SessionYearFetchSuccess) {
      final appConfig = context
          .read<AppConfigurationCubit>()
          .getAppConfiguration();
      final defaultId = appConfig.sessionYear.id;
      setState(() {
        _selectedSessionYear = sessionYearState.sessionYearList.firstWhere(
          (e) => e.id == defaultId,
          orElse: () => sessionYearState.sessionYearList.first,
        );
      });
      _fetchData();
    }
  }

  void _fetchData() {
    if (_selectedSessionYear == null) return;
    _fetchStats();
  }

  // ── Stats (ExamReportCubit) ─────────────────────────────────────────────

  void _fetchStats() {
    if (_selectedExamType == ExamType.offline) {
      context.read<ExamReportCubit>().fetchOfflineExamReport(
        sessionYearId: _selectedSessionYear!.id,
        childId: widget.childId,
      );
    } else {
      context.read<ExamReportCubit>().fetchOnlineExamReport(
        sessionYearId: _selectedSessionYear!.id,
        childId: widget.childId,
        subjectId: _selectedSubject?.id,
      );
    }
  }

  // ── Results Fetching via Cubits ────────────────────────────────────────

  void _fetchResults() {
    if (_selectedExamType == ExamType.offline) {
      context.read<ResultsCubit>().fetchResults(
        useParentApi: context.read<AuthCubit>().isParent(),
        childId: widget.childId ?? 0,
        sessionYearId: _selectedSessionYear!.id,
      );
    } else {
      context.read<ResultsOnlineCubit>().fetchResultsOnline(
        useParentApi: context.read<AuthCubit>().isParent(),
        childId: widget.childId ?? 0,
        subjectId: _selectedSubject?.id ?? 0,
        sessionYearId: _selectedSessionYear!.id,
      );
    }
  }

  // ── Exam-type picker bottomsheet ─────────────────────────────────────────

  Future<void> _showExamTypePicker() async {
    final ExamType? selected = await UiUtils.showBottomSheet(
      context: context,
      child: _ExamTypePickerSheet(current: _selectedExamType),
    );
    if (selected != null && selected != _selectedExamType) {
      setState(() {
        _selectedExamType = selected;
        _selectedSubject = null;
      });
      _fetchData();
    }
  }

  // ── Subject picker bottomsheet ────────────────────────────────────────────

  Future<void> _showSubjectPicker() async {
    // Build subject list from dashboard / parent's child subjects
    final List<Subject> subjectList =
        (widget.subjects ?? context.read<StudentDashboardCubit>().getSubjects())
            .where((element) => element.id != 0)
            .toList();
    if (subjectList.isEmpty) return;

    final Subject? selected = await UiUtils.showBottomSheet(
      context: context,
      child: SubjectPickerBottomsheetContainer(
        subjects: subjectList,
        selectedSubject: _selectedSubject,
      ),
    );

    // null means "All Subjects" was selected
    if (selected == null && _selectedSubject != null) {
      setState(() => _selectedSubject = null);
      _fetchData();
    } else if (selected != null && selected.id != _selectedSubject?.id) {
      setState(() => _selectedSubject = selected);
      _fetchData();
    }
  }

  // ─────────────────────────────────────────────────────────────────────────
  // Build
  // ─────────────────────────────────────────────────────────────────────────

  Widget _buildContentShimmer() {
    return Column(
      children: [
        const SizedBox(height: 15),
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 20),
          child: ShimmerLoadingContainer(
            child: CustomShimmerContainer(height: 150, borderRadius: 10),
          ),
        ),
        const SizedBox(height: 15),
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 20),
          child: ShimmerLoadingContainer(
            child: CustomShimmerContainer(height: 200, borderRadius: 10),
          ),
        ),
      ],
    );
  }

  @override
  Widget build(BuildContext context) {
    super.build(context);

    return SingleChildScrollView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: EdgeInsets.only(
        bottom: UiUtils.getScrollViewBottomPadding(context),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _buildFilters(),
          BlocConsumer<ExamReportCubit, ExamReportState>(
            listener: (context, state) {
              if (state is OfflineExamReportFetchSuccess ||
                  state is OnlineExamReportFetchSuccess) {
                _fetchResults();
              }
            },
            builder: (context, state) {
              if (state is ExamReportFetchInProgress ||
                  state is ExamReportInitial) {
                return _buildContentShimmer();
              }

              if (state is ExamReportFetchFailure) {
                return Padding(
                  padding: const EdgeInsets.all(20),
                  child: ErrorContainer(
                    errorMessageCode: state.errorMessage,
                    onTapRetry: _fetchData,
                  ),
                );
              }

              if (state is OfflineExamReportFetchSuccess) {
                return Column(
                  children: [
                    _buildStatsCard(state.examData),
                    _buildPerformanceSection(state.examData),
                    const SizedBox(height: 10),
                    _buildResultsSection(),
                  ],
                );
              }
              if (state is OnlineExamReportFetchSuccess) {
                return Column(
                  children: [
                    _buildStatsCard(state.examData),
                    _buildPerformanceSection(state.examData),
                    const SizedBox(height: 10),
                    _buildResultsSection(),
                  ],
                );
              }
              return const SizedBox.shrink();
            },
          ),
          const SizedBox(height: 20),
        ],
      ),
    );
  }

  // ── Filters row ────────────────────────────────────────────────────────

  Widget _buildFilters() {
    final sessionYearState = context.read<SessionYearCubit>().state;
    if (sessionYearState is! SessionYearFetchSuccess ||
        _selectedSessionYear == null) {
      return const SizedBox.shrink();
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
          child: Row(
            children: [
              // Session Year
              Expanded(
                child: CustomDropdownButtonContainer(
                  selectedValue: _selectedSessionYear!.name,
                  onTap: () async {
                    final SessionYear? selected = await UiUtils.showBottomSheet(
                      child: SessionYearPickerBottomsheetContainer(
                        selectedSessionYear: _selectedSessionYear!,
                        sessionYearList: sessionYearState.sessionYearList,
                      ),
                      context: context,
                    );
                    if (selected != null &&
                        selected.id != _selectedSessionYear?.id) {
                      setState(() => _selectedSessionYear = selected);
                      _fetchData();
                    }
                  },
                ),
              ),
              const SizedBox(width: 15),
              // Exam Type dropdown
              Expanded(
                child: CustomDropdownButtonContainer(
                  selectedValue: UiUtils.getTranslatedLabel(
                    context,
                    _selectedExamType == ExamType.offline
                        ? offlineKey
                        : onlineKey,
                  ),
                  onTap: _showExamTypePicker,
                ),
              ),
            ],
          ),
        ),
        // Subject filter — only for online
        if (_selectedExamType == ExamType.online)
          Padding(
            padding: const EdgeInsets.fromLTRB(20, 0, 20, 10),
            child: CustomDropdownButtonContainer(
              selectedValue:
                  _selectedSubject?.name ??
                  UiUtils.getTranslatedLabel(context, allSubjectsKey),
              onTap: _showSubjectPicker,
            ),
          ),
      ],
    );
  }

  // ── Stats card ─────────────────────────────────────────────────────────

  Widget _buildStatsCard(dynamic data) {
    final num avgPct = data.averagePercentage;
    final int totalExams = data.totalExams;

    // For offline
    final String avgGrade = _selectedExamType == ExamType.offline
        ? data.avgGrade
        : '-';
    final num passPct = _selectedExamType == ExamType.offline
        ? data.passPercentage
        : 0.0;

    // For online
    final num attemptedPct = _selectedExamType == ExamType.online
        ? data.attemptedPercentage
        : 0.0;

    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.circular(8),
        border: Border.all(
          color: Theme.of(
            context,
          ).colorScheme.onSurface.withValues(alpha: 0.25),
        ),
      ),
      child: Column(
        children: [
          Text(
            UiUtils.getTranslatedLabel(context, averagePercentageKey),
            style: TextStyle(
              fontSize: 12,
              color: Theme.of(
                context,
              ).colorScheme.onSurface.withValues(alpha: 0.6),
            ),
          ),
          const SizedBox(height: 4),
          Text(
            '${avgPct.toStringAsFixed(0)}%',
            style: TextStyle(
              fontSize: 24,
              fontWeight: FontWeight.bold,
              color: Theme.of(context).colorScheme.primary,
            ),
          ),
          const SizedBox(height: 8),
          Divider(
            color: Theme.of(
              context,
            ).colorScheme.onSurface.withValues(alpha: 0.25),
          ),
          const SizedBox(height: 8),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceEvenly,
            children: [
              if (_selectedExamType == ExamType.offline) ...[
                _buildStatItem(
                  avgGrade,
                  UiUtils.getTranslatedLabel(context, avgGradeKey),
                  Theme.of(context).colorScheme.primary,
                ),
                Container(
                  width: 1,
                  height: 36,
                  color: Theme.of(
                    context,
                  ).colorScheme.onSurface.withValues(alpha: 0.2),
                ),
              ],
              _buildStatItem(
                '$totalExams',
                UiUtils.getTranslatedLabel(context, totalExamKey),
                Theme.of(context).colorScheme.primary,
              ),
              Container(
                width: 1,
                height: 36,
                color: Theme.of(
                  context,
                ).colorScheme.onSurface.withValues(alpha: 0.2),
              ),
              if (_selectedExamType == ExamType.offline)
                _buildStatItem(
                  '${passPct.toStringAsFixed(0)}%',
                  UiUtils.getTranslatedLabel(context, passKey),
                  Theme.of(context).colorScheme.primary,
                )
              else
                _buildStatItem(
                  '${attemptedPct.toStringAsFixed(0)}%',
                  UiUtils.getTranslatedLabel(context, attemptedKey),
                  Theme.of(context).colorScheme.primary,
                ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildStatItem(String value, String label, Color color) {
    return Column(
      children: [
        Text(
          value,
          style: TextStyle(
            fontSize: 18,
            fontWeight: FontWeight.bold,
            color: color,
          ),
        ),
        const SizedBox(height: 4),
        Text(
          label,
          style: TextStyle(
            fontSize: 12,
            color: Theme.of(
              context,
            ).colorScheme.onSurface.withValues(alpha: 0.6),
          ),
        ),
      ],
    );
  }

  // ── Performance section ────────────────────────────────────────────────

  Widget _buildPerformanceSection(dynamic data) {
    final int totalExams = data.totalExams;
    final int missedCount = data.missedCount;

    // For offline
    final int passCount = _selectedExamType == ExamType.offline
        ? data.passCount
        : 0;
    final int failCount = _selectedExamType == ExamType.offline
        ? data.failCount
        : 0;

    // For online
    final int attemptedCount = _selectedExamType == ExamType.online
        ? data.attemptedCount
        : 0;

    final String? bestName = data.bestExamName;
    final num bestPct = data.bestExamPercentage;
    final String? worstName = data.worstExamName;
    final num worstPct = data.worstExamPercentage;

    if (totalExams == 0 && (bestName == null || bestName.isEmpty)) {
      return const SizedBox.shrink();
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
          child: Text(
            UiUtils.getTranslatedLabel(context, examPerformanceKey),
            style: TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.bold,
              color: Theme.of(context).colorScheme.onSurface,
            ),
          ),
        ),
        Container(
          margin: const EdgeInsets.symmetric(horizontal: 20),
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: Theme.of(context).colorScheme.surface,
            borderRadius: BorderRadius.circular(8),
            border: Border.all(
              color: Theme.of(
                context,
              ).colorScheme.onSurface.withValues(alpha: 0.25),
            ),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              if (totalExams > 0)
                Row(
                  children: [
                    SizedBox(
                      height: 110,
                      width: 110,
                      child: Stack(
                        alignment: Alignment.center,
                        children: [
                          PieChart(
                            PieChartData(
                              sectionsSpace: 2,
                              centerSpaceRadius: 42,
                              sections: [
                                if (_selectedExamType == ExamType.offline) ...[
                                  PieChartSectionData(
                                    color: StatusColors.of(context).success,
                                    value: passCount > 0
                                        ? passCount.toDouble()
                                        : 0.001,
                                    title: '',
                                    radius: 12,
                                  ),
                                  PieChartSectionData(
                                    color: StatusColors.of(context).danger,
                                    value: failCount > 0
                                        ? failCount.toDouble()
                                        : 0.001,
                                    title: '',
                                    radius: 12,
                                  ),
                                ] else ...[
                                  PieChartSectionData(
                                    color: StatusColors.of(context).success,
                                    value: attemptedCount > 0
                                        ? attemptedCount.toDouble()
                                        : 0.001,
                                    title: '',
                                    radius: 12,
                                  ),
                                ],
                                PieChartSectionData(
                                  color: StatusColors.of(context).warning,
                                  value: missedCount > 0
                                      ? missedCount.toDouble()
                                      : 0.001,
                                  title: '',
                                  radius: 12,
                                ),
                              ],
                            ),
                          ),
                          Column(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Text(
                                UiUtils.getTranslatedLabel(
                                  context,
                                  totalExamKey,
                                ),
                                textAlign: TextAlign.center,
                                style: TextStyle(
                                  fontSize: 9,
                                  color: Theme.of(context).colorScheme.onSurface
                                      .withValues(alpha: 0.6),
                                ),
                              ),
                              Text(
                                '$totalExams',
                                style: TextStyle(
                                  fontSize: 16,
                                  fontWeight: FontWeight.bold,
                                  color: Theme.of(
                                    context,
                                  ).colorScheme.onSurface,
                                ),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(width: 20),
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        if (_selectedExamType == ExamType.offline) ...[
                          _buildLegendItem(
                            StatusColors.of(context).success,
                            '${UiUtils.getTranslatedLabel(context, passKey)} : $passCount',
                          ),
                          const SizedBox(height: 8),
                          _buildLegendItem(
                            StatusColors.of(context).danger,
                            '${UiUtils.getTranslatedLabel(context, failedKey)} : $failCount',
                          ),
                        ] else ...[
                          _buildLegendItem(
                            StatusColors.of(context).success,
                            '${UiUtils.getTranslatedLabel(context, attemptedKey)} : $attemptedCount',
                          ),
                        ],
                        const SizedBox(height: 8),
                        _buildLegendItem(
                          StatusColors.of(context).warning,
                          '${UiUtils.getTranslatedLabel(context, missedKey)} : $missedCount',
                        ),
                      ],
                    ),
                  ],
                ),
              if (bestName != null && bestName.isNotEmpty) ...[
                const SizedBox(height: 15),
                Text(
                  UiUtils.getTranslatedLabel(context, bestExamPerformanceKey),
                  style: TextStyle(
                    fontSize: 12,
                    color: Theme.of(
                      context,
                    ).colorScheme.onSurface.withValues(alpha: 0.5),
                  ),
                ),
                const SizedBox(height: 6),
                _buildPerformanceBar(
                  bestName,
                  bestPct,
                  Theme.of(context).colorScheme.primary,
                ),
              ],
              if (worstName != null && worstName.isNotEmpty) ...[
                const SizedBox(height: 15),
                Text(
                  UiUtils.getTranslatedLabel(context, worstExamPerformanceKey),
                  style: TextStyle(
                    fontSize: 12,
                    color: Theme.of(
                      context,
                    ).colorScheme.onSurface.withValues(alpha: 0.5),
                  ),
                ),
                const SizedBox(height: 6),
                _buildPerformanceBar(
                  worstName,
                  worstPct,
                  StatusColors.of(context).danger,
                ),
              ],
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildPerformanceBar(String name, num pct, Color color) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Expanded(
              child: Text(
                name,
                style: TextStyle(
                  fontSize: 13,
                  color: Theme.of(context).colorScheme.onSurface,
                ),
                overflow: TextOverflow.ellipsis,
              ),
            ),
            Text(
              '${pct.toStringAsFixed(0)}%',
              style: TextStyle(
                fontSize: 13,
                fontWeight: FontWeight.bold,
                color: Theme.of(context).colorScheme.onSurface,
              ),
            ),
          ],
        ),
        const SizedBox(height: 5),
        ClipRRect(
          borderRadius: BorderRadius.circular(4),
          child: LinearProgressIndicator(
            value: pct / 100,
            minHeight: 8,
            backgroundColor: color.withValues(alpha: 0.15),
            valueColor: AlwaysStoppedAnimation<Color>(color),
          ),
        ),
      ],
    );
  }

  Widget _buildLegendItem(Color color, String text) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Container(
          width: 10,
          height: 10,
          decoration: BoxDecoration(color: color, shape: BoxShape.circle),
        ),
        const SizedBox(width: 6),
        Text(
          text,
          style: TextStyle(
            fontSize: 13,
            color: Theme.of(context).colorScheme.onSurface,
          ),
        ),
      ],
    );
  }

  // ── Results section (no pagination) ───────────────────────────────────

  Widget _buildResultsSection() {
    final isOnline = _selectedExamType == ExamType.online;
    final sectionTitle = UiUtils.getTranslatedLabel(
      context,
      isOnline ? recentOnlineExamResultKey : recentOfflineExamResultKey,
    );
    final noDataKey = isOnline
        ? noOnlineExamResultsFoundKey
        : noOfflineExamResultsFoundKey;

    if (isOnline) {
      return BlocBuilder<ResultsOnlineCubit, ResultsOnlineState>(
        builder: (context, state) {
          if (state is ResultsOnlineFetchInProgress) {
            return _buildResultsLoading();
          }
          if (state is ResultsOnlineFetchFailure) {
            return _buildResultsError(state.errorMessage);
          }
          if (state is ResultsOnlineFetchSuccess) {
            return _buildResultsList(state.results, sectionTitle, noDataKey);
          }
          return const SizedBox.shrink();
        },
      );
    } else {
      return BlocBuilder<ResultsCubit, ResultsState>(
        builder: (context, state) {
          if (state is ResultsFetchInProgress) {
            return _buildResultsLoading();
          }
          if (state is ResultsFetchFailure) {
            return _buildResultsError(state.errorMessage);
          }
          if (state is ResultsFetchSuccess) {
            return _buildResultsList(state.results, sectionTitle, noDataKey);
          }
          return const SizedBox.shrink();
        },
      );
    }
  }

  Widget _buildResultsLoading() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(20, 10, 20, 8),
          child: ShimmerLoadingContainer(
            child: CustomShimmerContainer(
              height: 20,
              width: 180,
              borderRadius: 4,
            ),
          ),
        ),
        ...List.generate(
          2,
          (_) => Padding(
            padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 6),
            child: ShimmerLoadingContainer(
              child: CustomShimmerContainer(height: 80, borderRadius: 10),
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildResultsError(String errorMsg) {
    return Padding(
      padding: const EdgeInsets.all(20),
      child: ErrorContainer(
        errorMessageCode: errorMsg,
        onTapRetry: _fetchResults,
      ),
    );
  }

  Widget _buildResultsList(
    List<dynamic> list,
    String sectionTitle,
    String noDataKey,
  ) {
    if (list.isEmpty) {
      return const SizedBox.shrink();
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(20, 10, 20, 10),
          child: Text(
            sectionTitle,
            style: TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.bold,
              color: Theme.of(context).colorScheme.onSurface,
            ),
          ),
        ),
        ...list.map((r) {
          if (r is Result) return _buildOfflineResultCard(r);
          if (r is ResultOnline) return _buildOnlineResultCard(r);
          return const SizedBox.shrink();
        }),
      ],
    );
  }

  Widget _buildOfflineResultCard(Result result) {
    return ListItemForExamAndResult(
      examStartingDate: result.examDate,
      examName: result.examName,
      resultGrade: result.grade,
      resultPercentage: result.percentage,
      onItemTap: () {
        Navigator.of(context).pushNamed(
          Routes.result,
          arguments: {'childId': widget.childId, 'result': result},
        );
      },
    );
  }

  Widget _buildOnlineResultCard(ResultOnline result) {
    return ListItemForOnlineExamAndOnlineResult(
      examStartingDate: result.examDate,
      examEndingDate: result.examDate,
      examName: result.examName,
      subjectName: result.subject.showType
          ? result.subject.subjectNameWithType
          : result.subject.name,
      totalMarks: result.totalMarks,
      marks: result.obtainedMarks,
      isSubjectSelected: false,
      onItemTap: () {
        Navigator.of(context).pushNamed(
          Routes.resultOnline,
          arguments: {
            'examId': result.examId,
            'examName': result.examName,
            'subjectName': result.subject.showType
                ? result.subject.subjectNameWithType
                : result.subject.name,
            'childId': widget.childId,
            'totalMarks': result.totalMarks,
            'obtainedMarks': result.obtainedMarks,
          },
        );
      },
    );
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// Exam Type Picker Bottomsheet
// ─────────────────────────────────────────────────────────────────────────────

class _ExamTypePickerSheet extends StatelessWidget {
  const _ExamTypePickerSheet({required this.current});
  final ExamType current;

  @override
  Widget build(BuildContext context) {
    final types = [ExamType.offline, ExamType.online];
    final labels = [offlineKey, onlineKey];

    return Container(
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.only(
          topLeft: Radius.circular(UiUtils.bottomSheetTopRadius),
          topRight: Radius.circular(UiUtils.bottomSheetTopRadius),
        ),
      ),
      constraints: BoxConstraints(
        maxHeight: MediaQuery.sizeOf(context).height * 0.4,
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          BottomSheetTopBarMenu(
            onTapCloseButton: () => Navigator.pop(context),
            padding: EdgeInsets.only(
              top: UiUtils.bottomSheetHorizontalContentPadding,
              left: UiUtils.bottomSheetHorizontalContentPadding,
              right: UiUtils.bottomSheetHorizontalContentPadding,
            ),
            title: UiUtils.getTranslatedLabel(context, filterByExamTypeKey),
          ),
          ...List.generate(types.length, (i) {
            final isSelected = types[i] == current;
            return Material(
              color: Colors.transparent,
              child: InkWell(
                onTap: () => Navigator.pop(context, types[i]),
                child: Padding(
                  padding: EdgeInsets.symmetric(
                    vertical: 12,
                    horizontal: UiUtils.bottomSheetHorizontalContentPadding,
                  ),
                  child: Row(
                    children: [
                      Expanded(
                        child: Text(
                          UiUtils.getTranslatedLabel(context, labels[i]),
                          style: TextStyle(
                            fontSize: 16,
                            color: Theme.of(context).colorScheme.onSurface,
                            fontWeight: FontWeight.w500,
                          ),
                        ),
                      ),
                      CustomRadioContainer(isSelected: isSelected),
                    ],
                  ),
                ),
              ),
            );
          }),
        ],
      ),
    );
  }
}
