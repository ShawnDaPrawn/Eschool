import 'package:eschool/app/routes.dart';
import 'package:eschool/cubits/appConfigurationCubit.dart';
import 'package:eschool/cubits/assignmentsCubit.dart';
import 'package:eschool/cubits/authCubit.dart';
import 'package:eschool/cubits/sessionYearCubit.dart';
import 'package:eschool/data/models/assignment.dart';
import 'package:eschool/data/models/sessionYear.dart';
import 'package:eschool/data/models/subject.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool/ui/screens/reports/cubits/assignmentReportCubit.dart';
import 'package:eschool/ui/screens/reports/models/assignmentReport.dart';
import 'package:eschool/ui/screens/reports/widgets/sessionYearPickerBottomsheetContainer.dart';
import 'package:eschool/ui/screens/reports/widgets/subjectPickerBottomsheetContainer.dart';
import 'package:eschool/utils/labelKeys.dart';
import 'package:eschool/utils/uiUtils.dart';
import 'package:fl_chart/fl_chart.dart';
import 'package:eschool/cubits/studentDashboardCubit.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

class AssignmentReportTab extends StatefulWidget {
  final int? childId;
  final List<Subject>? subjects;
  const AssignmentReportTab({super.key, this.childId, this.subjects});

  @override
  State<AssignmentReportTab> createState() => _AssignmentReportTabState();

  static Widget buildShimmer(BuildContext context) {
    return SingleChildScrollView(
      padding: EdgeInsets.only(
        top: 0,
        bottom: context.read<AuthCubit>().isParent()
            ? 0
            : UiUtils.getScrollViewBottomPadding(context),
      ),
      child: Center(
        child: ShimmerLoadingContainer(
          child: Column(
            children: [
              const SizedBox(height: 20),
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 20),
                child: Row(
                  children: [
                    Expanded(
                      child: CustomShimmerContainer(
                        height: 48,
                        borderRadius: 8,
                      ),
                    ),
                    const SizedBox(width: 15),
                    Expanded(
                      child: CustomShimmerContainer(
                        height: 48,
                        borderRadius: 8,
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 15),
              CustomShimmerContainer(
                margin: const EdgeInsets.symmetric(horizontal: 20),
                height: 100,
                borderRadius: 10,
              ),
              const SizedBox(height: 20),
              CustomShimmerContainer(
                margin: const EdgeInsets.symmetric(horizontal: 20),
                height: 180,
                borderRadius: 10,
              ),
              const SizedBox(height: 20),
              CustomShimmerContainer(
                margin: const EdgeInsets.symmetric(horizontal: 20),
                height: 80,
                borderRadius: 10,
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _AssignmentReportTabState extends State<AssignmentReportTab>
    with AutomaticKeepAliveClientMixin {
  SessionYear? _selectedSessionYear;
  Subject? _selectedSubject;
  late final ScrollController _scrollController;

  @override
  bool get wantKeepAlive => true;

  @override
  void initState() {
    super.initState();
    _scrollController = ScrollController()..addListener(_scrollListener);
    Future.delayed(Duration.zero, _initializeSessionYear);
  }

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

  @override
  void dispose() {
    _scrollController.removeListener(_scrollListener);
    _scrollController.dispose();
    super.dispose();
  }

  void _scrollListener() {
    if (_scrollController.offset >=
        _scrollController.position.maxScrollExtent) {
      if (context.read<AssignmentsCubit>().hasMore()) {
        context.read<AssignmentsCubit>().fetchMoreAssignments(
          childId: widget.childId ?? 0,

          useParentApi: context.read<AuthCubit>().isParent(),
        );
      }
    }
  }

  void _fetchData() {
    if (_selectedSessionYear == null) return;
    context.read<AssignmentReportCubit>().fetchAssignmentReport(
      sessionYearId: _selectedSessionYear!.id,
      childId: widget.childId,
      subjectId: _selectedSubject?.id,
    );
    context.read<AssignmentsCubit>().fetchAssignments(
      childId: widget.childId ?? 0,
      useParentApi: context.read<AuthCubit>().isParent(),
      sessionYearId: _selectedSessionYear!.id,
      subjectId: _selectedSubject?.id,
    );
  }

  Future<void> _showSubjectPicker() async {
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

    if (selected == null && _selectedSubject != null) {
      setState(() => _selectedSubject = null);
      _fetchData();
    } else if (selected != null && selected.id != _selectedSubject?.id) {
      setState(() => _selectedSubject = selected);
      _fetchData();
    }
  }

  Widget _buildContentShimmer() {
    return Center(
      child: ShimmerLoadingContainer(
        child: Column(
          children: [
            const SizedBox(height: 20),
            CustomShimmerContainer(
              margin: const EdgeInsets.symmetric(horizontal: 20),
              height: 100,
              borderRadius: 10,
            ),
            const SizedBox(height: 20),
            CustomShimmerContainer(
              margin: const EdgeInsets.symmetric(horizontal: 20),
              height: 180,
              borderRadius: 10,
            ),
            const SizedBox(height: 20),
            CustomShimmerContainer(
              margin: const EdgeInsets.symmetric(horizontal: 20),
              height: 80,
              borderRadius: 10,
            ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    super.build(context);
    return SingleChildScrollView(
      controller: _scrollController,
      padding: EdgeInsets.only(
        top: 0,
        bottom: context.read<AuthCubit>().isParent()
            ? 0
            : UiUtils.getScrollViewBottomPadding(context),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _buildFilters(),
          BlocBuilder<AssignmentReportCubit, AssignmentReportState>(
            builder: (context, state) {
              if (state is AssignmentReportFetchInProgress) {
                return _buildContentShimmer();
              }
              if (state is AssignmentReportFetchFailure) {
                return Center(
                  child: ErrorContainer(
                    errorMessageCode: state.errorMessage,
                    onTapRetry: _fetchData,
                  ),
                );
              }

              if (state is AssignmentReportFetchSuccess) {
                final data = state.assignmentReport;
                return Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    _buildAverageCompletionRate(data),
                    _buildAssignmentOverview(data),
                    _buildRecentAssignmentsList(),
                  ],
                );
              }
              return const SizedBox.shrink();
            },
          ),
        ],
      ),
    );
  }

  Widget _buildFilters() {
    final sessionYearState = context.read<SessionYearCubit>().state;
    if (sessionYearState is! SessionYearFetchSuccess ||
        _selectedSessionYear == null) {
      return const SizedBox.shrink();
    }

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
      child: Row(
        children: [
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
          Expanded(
            child: CustomDropdownButtonContainer(
              selectedValue:
                  _selectedSubject?.name ??
                  UiUtils.getTranslatedLabel(context, allSubjectsKey),
              onTap: _showSubjectPicker,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildAverageCompletionRate(AssignmentReport data) {
    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
      padding: const EdgeInsets.symmetric(vertical: 20),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.circular(8),
        border: Border.all(
          color: Theme.of(
            context,
          ).colorScheme.onSurface.withValues(alpha: 0.25),
        ),
      ),
      child: Center(
        child: Column(
          children: [
            Text(
              UiUtils.getTranslatedLabel(context, averageCompletionRateKey),
              style: TextStyle(
                fontSize: 14,
                color: Theme.of(
                  context,
                ).colorScheme.onSurface.withValues(alpha: 0.7),
              ),
            ),
            const SizedBox(height: 8),
            Text(
              '${data.averageCompletionRate.toStringAsFixed(0)}%',
              style: TextStyle(
                fontSize: 32,
                fontWeight: FontWeight.bold,
                color: Theme.of(context).colorScheme.primary,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildAssignmentOverview(AssignmentReport data) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
          child: Text(
            UiUtils.getTranslatedLabel(context, assignmentOverviewKey),
            style: TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.bold,
              color: Theme.of(context).colorScheme.onSurface,
            ),
          ),
        ),
        Container(
          margin: const EdgeInsets.symmetric(
            horizontal: 20,
          ).add(EdgeInsetsGeometry.only(bottom: 10)),
          padding: const EdgeInsets.all(15),
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
              Row(
                children: [
                  SizedBox(
                    width: 120,
                    height: 120,
                    child: Stack(
                      alignment: Alignment.center,
                      children: [
                        PieChart(
                          PieChartData(
                            sectionsSpace: 0,
                            centerSpaceRadius: 45,
                            sections: [
                              if (data.passCount > 0)
                                PieChartSectionData(
                                  color: StatusColors.of(context).success,
                                  value: data.passCount.toDouble(),
                                  title: '',
                                  radius: 12,
                                ),
                              if (data.pendingCount > 0)
                                PieChartSectionData(
                                  color: StatusColors.of(context).warning,
                                  value: data.pendingCount.toDouble(),
                                  title: '',
                                  radius: 12,
                                ),
                              if (data.missedCount > 0)
                                PieChartSectionData(
                                  color: StatusColors.of(context).danger,
                                  value: data.missedCount.toDouble(),
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
                              UiUtils.getTranslatedLabel(context, totalKey),
                              style: TextStyle(
                                fontSize: 10,
                                color: Theme.of(
                                  context,
                                ).colorScheme.onSurface.withValues(alpha: 0.7),
                              ),
                            ),
                            Text(
                              '${data.totalAssignments}',
                              style: TextStyle(
                                fontSize: 20,
                                fontWeight: FontWeight.bold,
                                color: Theme.of(context).colorScheme.onSurface,
                              ),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 24),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        _buildLegendItem(
                          StatusColors.of(context).success,
                          "${UiUtils.getTranslatedLabel(context, passKey)} : ${data.passCount}",
                        ),
                        const SizedBox(height: 8),
                        _buildLegendItem(
                          StatusColors.of(context).warning,
                          "${UiUtils.getTranslatedLabel(context, pendingKey)} : ${data.pendingCount}",
                        ),
                        const SizedBox(height: 8),
                        _buildLegendItem(
                          StatusColors.of(context).danger,
                          "${UiUtils.getTranslatedLabel(context, missedKey)} : ${data.missedCount}",
                        ),
                      ],
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 15),
              Row(
                children: [
                  Expanded(
                    child: _buildSmallStatCard(
                      title: UiUtils.getTranslatedLabel(
                        context,
                        onTimeSubmissionKey,
                      ),
                      value: "${data.onTimeSubmission}",
                      bgColor: StatusColors.of(
                        context,
                      ).success.withValues(alpha: 0.2),
                    ),
                  ),
                  const SizedBox(width: 15),
                  Expanded(
                    child: _buildSmallStatCard(
                      title: UiUtils.getTranslatedLabel(
                        context,
                        lateSubmissionKey,
                      ),
                      value: "${data.lateSubmission}",
                      bgColor: StatusColors.of(
                        context,
                      ).warning.withValues(alpha: 0.2),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildSmallStatCard({
    required String title,
    required String value,
    required Color bgColor,
  }) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: bgColor,
        borderRadius: BorderRadius.circular(10),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: TextStyle(
              fontSize: 12,
              color: Theme.of(
                context,
              ).colorScheme.onSurface.withValues(alpha: 0.8),
            ),
          ),
          const SizedBox(height: 4),
          Text(
            value,
            style: TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.bold,
              color: Theme.of(context).colorScheme.onSurface,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildLegendItem(Color color, String text) {
    return Row(
      children: [
        Container(
          width: 8,
          height: 8,
          decoration: BoxDecoration(color: color, shape: BoxShape.circle),
        ),
        const SizedBox(width: 8),
        Text(
          text,
          style: TextStyle(
            fontSize: 13,
            color: Theme.of(
              context,
            ).colorScheme.onSurface.withValues(alpha: 0.8),
          ),
        ),
      ],
    );
  }

  Widget _buildRecentAssignmentsList() {
    return BlocBuilder<AssignmentsCubit, AssignmentsState>(
      builder: (context, state) {
        if (state is AssignmentsFetchSuccess) {
          if (state.assignments.isEmpty) {
            return const SizedBox.shrink();
          }
          return Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Padding(
                padding: const EdgeInsets.symmetric(
                  horizontal: 20,
                  vertical: 15,
                ),
                child: Text(
                  UiUtils.getTranslatedLabel(context, recentAssignmentKey),
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                    color: Theme.of(context).colorScheme.onSurface,
                  ),
                ),
              ),
              ...state.assignments.map(
                (assignment) => _buildAssignmentCard(assignment),
              ),
              if (state.fetchMoreAssignmentsInProgress)
                const Padding(
                  padding: EdgeInsets.all(8.0),
                  child: CircularProgressIndicator(),
                ),
            ],
          );
        }
        if (state is AssignmentsFetchInProgress) {
          return Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Padding(
                padding: const EdgeInsets.symmetric(
                  horizontal: 20,
                  vertical: 15,
                ),
                child: Text(
                  UiUtils.getTranslatedLabel(context, recentAssignmentKey),
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                    color: Theme.of(context).colorScheme.onSurface,
                  ),
                ),
              ),
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 20),
                child: CustomShimmerContainer(height: 100, borderRadius: 10),
              ),
            ],
          );
        }
        return const SizedBox.shrink();
      },
    );
  }

  Widget _buildStatusBadge(Assignment assignment) {
    final assignmentStatus = UiUtils.getAssignmentSubmissionStatusKey(
      assignment.assignmentSubmission.status,
    );

    final bool didMissAssignment = DateTime.now().isAfter(
      assignment.dueDate.add(
        Duration(days: assignment.extraDaysForResubmission),
      ),
    );

    String status = UiUtils.getTranslatedLabel(
      context,
      assignmentStatus.isEmpty
          ? didMissAssignment
                ? missedKey
                : pendingKey
          : assignmentStatus,
    );
    Color badgeColor = StatusColors.of(context).warning;

    if (assignment.assignmentSubmission.status == 1 ||
        assignment.assignmentSubmission.status == 3) {
      badgeColor = StatusColors.of(context).success;
    } else if (assignment.assignmentSubmission.status == 2 ||
        didMissAssignment) {
      badgeColor = StatusColors.of(context).danger;
    } else if (assignment.assignmentSubmission.status == 0) {
      badgeColor = StatusColors.of(context).info;
    }
    return Container(
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(4),
        color: badgeColor.withValues(alpha: 0.25),
      ),
      padding: const EdgeInsets.symmetric(vertical: 4, horizontal: 8),
      child: Text(
        status,
        style: TextStyle(color: badgeColor, fontWeight: FontWeight.w600),
      ),
    );
  }

  Widget _buildAssignmentCard(Assignment assignment) {
    return GestureDetector(
      onTap: () {
        Navigator.of(
          context,
        ).pushNamed<Assignment>(Routes.assignment, arguments: assignment).then((
          submittedAssignment,
        ) {
          if (submittedAssignment != null) {
            //change submitted assignment status on dashboard
            context.read<StudentDashboardCubit>().updateAssignments(
              submittedAssignment,
            );

            //update the same assignment if existing in the list in assignments cubit
            context.read<AssignmentsCubit>().updateAssignments(
              submittedAssignment,
            );
          }
        });
      },
      child: Container(
        margin: const EdgeInsets.symmetric(horizontal: 20, vertical: 8),
        padding: const EdgeInsets.all(15),
        decoration: BoxDecoration(
          color: Theme.of(context).colorScheme.surfaceContainerLow,
          borderRadius: BorderRadius.circular(12),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Expanded(
                  child: Text(
                    assignment.name,
                    style: TextStyle(
                      fontSize: 14,
                      fontWeight: FontWeight.bold,
                      color: Theme.of(context).colorScheme.onSurface,
                    ),
                  ),
                ),
                _buildStatusBadge(assignment),
              ],
            ),
            const SizedBox(height: 8),
            Text(
              assignment.subject.name,
              style: TextStyle(
                fontSize: 13,
                color: Theme.of(
                  context,
                ).colorScheme.onSurface.withValues(alpha: 0.8),
              ),
            ),
            const SizedBox(height: 15),
            Text(
              "${UiUtils.getTranslatedLabel(context, dueKey)} ${UiUtils.formatDate(assignment.dueDate)}",
              style: TextStyle(
                fontSize: 12,
                color: Theme.of(
                  context,
                ).colorScheme.onSurface.withValues(alpha: 0.6),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
