import 'package:eschool/cubits/authCubit.dart';
import 'package:eschool/cubits/leavesCubit.dart';
import 'package:eschool/cubits/sessionYearCubit.dart';
import 'package:eschool/data/models/sessionYear.dart';
import 'package:eschool/ui/screens/leave/widgets/dropdownButtonContainer.dart';
import 'package:eschool/ui/screens/leave/widgets/leaveContainer.dart';
import 'package:eschool/ui/screens/reports/cubits/leaveReportCubit.dart';
import 'package:eschool/ui/screens/reports/models/leaveReport.dart';
import 'package:eschool/ui/screens/reports/widgets/sessionYearPickerBottomsheetContainer.dart';
import 'package:eschool/ui/widgets/customShimmerContainer.dart';
import 'package:eschool/ui/widgets/errorContainer.dart';
import 'package:eschool/ui/widgets/shimmerLoadingContainer.dart';
import 'package:eschool/utils/labelKeys.dart';
import 'package:eschool/utils/uiUtils.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:fl_chart/fl_chart.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

class LeaveReportTab extends StatefulWidget {
  final int? childId;
  const LeaveReportTab({super.key, this.childId});

  @override
  State<LeaveReportTab> createState() => _LeaveReportTabState();

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
                child: CustomShimmerContainer(height: 40, borderRadius: 10),
              ),
              const SizedBox(height: 20),
              CustomShimmerContainer(
                margin: const EdgeInsets.symmetric(horizontal: 20),
                height: 150,
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
                height: 180,
                borderRadius: 10,
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _LeaveReportTabState extends State<LeaveReportTab>
    with AutomaticKeepAliveClientMixin {
  SessionYear? _selectedSessionYear;
  late final ScrollController _scrollController;

  @override
  bool get wantKeepAlive => true;

  @override
  void initState() {
    super.initState();
    _scrollController = ScrollController();
    Future.delayed(Duration.zero, () {
      final sessionYearState = context.read<SessionYearCubit>().state;
      if (sessionYearState is SessionYearFetchSuccess) {
        final activeYear = sessionYearState.sessionYearList.firstWhere(
          (year) => year.defaultValue == 1,
          orElse: () => sessionYearState.sessionYearList.first,
        );
        setState(() {
          _selectedSessionYear = activeYear;
        });
        _fetchData();
      }
    });
  }

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  void _fetchData() {
    if (_selectedSessionYear == null) return;
    context.read<LeaveReportCubit>().fetchLeaveReport(
      sessionYearId: _selectedSessionYear!.id,
      childId: widget.childId,
    );
    context.read<LeaveCubit>().fetchLeaves(
      childId: widget.childId ?? 0,
      isParent: context.read<AuthCubit>().isParent(),
      sessionYearId: _selectedSessionYear!.id,
    );
  }

  Widget _buildContentShimmer() {
    return Center(
      child: ShimmerLoadingContainer(
        child: Column(
          children: [
            const SizedBox(height: 20),
            CustomShimmerContainer(
              margin: const EdgeInsets.symmetric(horizontal: 20),
              height: 150,
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
              height: 180,
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
          BlocBuilder<LeaveReportCubit, LeaveReportState>(
            builder: (context, state) {
              if (state is LeaveReportFetchInProgress) {
                return _buildContentShimmer();
              }
              if (state is LeaveReportFetchFailure) {
                return Center(
                  child: ErrorContainer(
                    errorMessageCode: state.errorMessage,
                    onTapRetry: _fetchData,
                  ),
                );
              }

              if (state is LeaveReportFetchSuccess) {
                final data = state.leaveReport;

                return Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [_buildLeaveOverview(data), _buildLeavesList()],
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
          if (selected != null && selected.id != _selectedSessionYear?.id) {
            setState(() => _selectedSessionYear = selected);
            _fetchData();
          }
        },
      ),
    );
  }

  Widget _buildLeaveOverview(LeaveReport data) {
    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 20, vertical: 15),
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
      child: Row(
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
                      if (data.completed > 0)
                        PieChartSectionData(
                          color: StatusColors.of(context).success,
                          value: data.completed.toDouble(),
                          title: '',
                          radius: 12,
                        ),
                      if (data.pending > 0)
                        PieChartSectionData(
                          color: StatusColors.of(context).warning,
                          value: data.pending.toDouble(),
                          title: '',
                          radius: 12,
                        ),
                      if (data.rejected > 0)
                        PieChartSectionData(
                          color: StatusColors.of(context).danger,
                          value: data.rejected.toDouble(),
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
                      UiUtils.getTranslatedLabel(context, totalLeaveKey),
                      style: TextStyle(
                        fontSize: 10,
                        color: Theme.of(
                          context,
                        ).colorScheme.onSurface.withValues(alpha: 0.7),
                      ),
                    ),
                    Text(
                      '${data.totalLeaves}',
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
                  "${UiUtils.getTranslatedLabel(context, completedKey)} : ${data.completed}",
                ),
                const SizedBox(height: 8),
                _buildLegendItem(
                  StatusColors.of(context).warning,
                  "${UiUtils.getTranslatedLabel(context, pendingKey)} : ${data.pending}",
                ),
                const SizedBox(height: 8),
                _buildLegendItem(
                  StatusColors.of(context).danger,
                  "${UiUtils.getTranslatedLabel(context, rejectedKey)} : ${data.rejected}",
                ),
              ],
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

  Widget _buildLeavesList() {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 20),
      child: BlocBuilder<LeaveCubit, LeaveState>(
        builder: (context, state) {
          if (state is LeaveFetchSuccess) {
            if (state.leaveList.isEmpty) {
              return const SizedBox.shrink();
            }
            return Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                ...state.leaveList.asMap().entries.map(
                  (entry) => LeaveContainer(
                    leave: entry.value,
                    index: entry.key,
                    hideDeleteButton: true,
                  ),
                ),
              ],
            );
          }
          if (state is LeaveFetchInProgress) {
            return Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Padding(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 20,
                    vertical: 10,
                  ),
                  child: CustomShimmerContainer(height: 180, borderRadius: 10),
                ),
              ],
            );
          }
          return const SizedBox.shrink();
        },
      ),
    );
  }
}
