import 'package:eschool/cubits/authCubit.dart';
import 'package:eschool/cubits/feesTransactionCubit.dart';
import 'package:eschool/cubits/sessionYearCubit.dart';
import 'package:eschool/cubits/appConfigurationCubit.dart';
import 'package:eschool/data/models/sessionYear.dart';
import 'package:eschool/ui/screens/fees/widgets/feeTransactionItemContainer.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool/ui/screens/reports/cubits/feesReportCubit.dart';
import 'package:eschool/ui/screens/reports/models/feesReport.dart';
import 'package:eschool/ui/screens/reports/widgets/sessionYearPickerBottomsheetContainer.dart';
import 'package:eschool/utils/labelKeys.dart';
import 'package:eschool/utils/uiUtils.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:fl_chart/fl_chart.dart';

class FeesReportTab extends StatefulWidget {
  final int? childId;
  const FeesReportTab({super.key, this.childId});

  @override
  State<FeesReportTab> createState() => _FeesReportTabState();

  static Widget buildShimmer(BuildContext context) {
    return SingleChildScrollView(
      padding: const EdgeInsets.only(top: 0),
      child: Center(
        child: ShimmerLoadingContainer(
          child: Column(
            children: [
              const SizedBox(height: 20),
              CustomShimmerContainer(
                height: 50,
                width: MediaQuery.sizeOf(context).width * 0.9,
              ),
              const SizedBox(height: 20),
              CustomShimmerContainer(
                height: 350,
                width: MediaQuery.sizeOf(context).width * 0.9,
              ),
              const SizedBox(height: 20),
              CustomShimmerContainer(
                height: 150,
                width: MediaQuery.sizeOf(context).width * 0.9,
              ),
              const SizedBox(height: 20),
              CustomShimmerContainer(
                height: 100,
                width: MediaQuery.sizeOf(context).width * 0.9,
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _FeesReportTabState extends State<FeesReportTab>
    with AutomaticKeepAliveClientMixin {
  SessionYear? _selectedSessionYear;
  late final ScrollController _scrollController = ScrollController()
    ..addListener(_scrollListener);

  @override
  bool get wantKeepAlive => true;

  @override
  void initState() {
    super.initState();
    Future.delayed(Duration.zero, () {
      _initializeSessionYear();
    });
  }

  void _initializeSessionYear() {
    final sessionYearState = context.read<SessionYearCubit>().state;
    if (sessionYearState is SessionYearFetchSuccess) {
      final appConfig = context
          .read<AppConfigurationCubit>()
          .getAppConfiguration();
      final defaultSessionYearId = appConfig.sessionYear.id;

      setState(() {
        _selectedSessionYear = sessionYearState.sessionYearList.firstWhere(
          (element) => element.id == defaultSessionYearId,
          orElse: () => sessionYearState.sessionYearList.first,
        );
      });

      _fetchData();
    }
  }

  void _fetchData() {
    if (_selectedSessionYear == null) return;

    // Fetch stats
    context.read<FeesReportCubit>().fetchFeesReport(
      sessionYearId: _selectedSessionYear!.id,
      childId: widget.childId,
    );

    // Fetch transactions
    context.read<FeesTransactionsCubit>().fetchFeesTransactions(
      page: 1,
      isStudentLoggedIn: !context.read<AuthCubit>().isParent(),
      childId: widget.childId,
      sessionYearId: _selectedSessionYear!.id,
    );
  }

  void _scrollListener() {
    if (_scrollController.offset >=
        _scrollController.position.maxScrollExtent) {
      if (context.read<FeesTransactionsCubit>().hasMore()) {
        context.read<FeesTransactionsCubit>().fetchMoreFeesTransactions(
          isStudentLoggedIn: !context.read<AuthCubit>().isParent(),
          childId: widget.childId,
          sessionYearId: _selectedSessionYear?.id,
        );
      }
    }
  }

  @override
  void dispose() {
    _scrollController.removeListener(_scrollListener);
    _scrollController.dispose();
    super.dispose();
  }

  Widget _buildSessionYearFilter() {
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
          final SessionYear? selectedYear = await UiUtils.showBottomSheet(
            child: SessionYearPickerBottomsheetContainer(
              selectedSessionYear: _selectedSessionYear!,
              sessionYearList: sessionYearState.sessionYearList,
            ),
            context: context,
          );
          if (selectedYear != null &&
              selectedYear.id != _selectedSessionYear?.id) {
            setState(() {
              _selectedSessionYear = selectedYear;
            });
            _fetchData();
          }
        },
      ),
    );
  }

  Widget _buildTopStatisticsCard(FeesReport data) {
    final num totalPayable = data.totalFeePayable;
    final num totalPaid = data.totalPaid;
    final num totalPending = data.totalPending;
    final String lastPayment = data.lastPaymentDate ?? '-';
    final String nextDue = data.nextDueDate ?? '-';

    final double paidPercentage = totalPayable > 0
        ? ((totalPaid / totalPayable) * 100).clamp(0, 100)
        : 0;

    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
      padding: const EdgeInsets.all(15),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(
          color: Theme.of(
            context,
          ).colorScheme.onSurface.withValues(alpha: 0.25),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      UiUtils.getTranslatedLabel(context, totalFeePayableKey),
                      style: TextStyle(
                        fontSize: 14,
                        color: Theme.of(
                          context,
                        ).colorScheme.onSurface.withValues(alpha: 0.7),
                      ),
                    ),
                    const SizedBox(height: 5),
                    Text(
                      UiUtils.formatAmount(
                        strVal: totalPayable.toStringAsFixed(0),
                        context: context,
                      ),
                      style: TextStyle(
                        fontSize: 28,
                        fontWeight: FontWeight.bold,
                        color: Theme.of(context).colorScheme.primary,
                      ),
                    ),
                  ],
                ),
              ),
              SizedBox(
                width: 90,
                height: 90,
                child: Stack(
                  children: [
                    PieChart(
                      PieChartData(
                        sectionsSpace: 0,
                        centerSpaceRadius: 35,
                        startDegreeOffset: -90,
                        sections: [
                          PieChartSectionData(
                            color: Theme.of(context).colorScheme.primary,
                            value: paidPercentage,
                            title: '',
                            radius: 10,
                          ),
                          PieChartSectionData(
                            color: Theme.of(
                              context,
                            ).colorScheme.onSurface.withValues(alpha: 0.1),
                            value: 100 - paidPercentage,
                            title: '',
                            radius: 10,
                          ),
                        ],
                      ),
                    ),
                    Center(
                      child: Column(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Text(
                            UiUtils.getTranslatedLabel(context, totalPayKey),
                            style: TextStyle(
                              fontSize: 9,
                              color: Theme.of(
                                context,
                              ).colorScheme.onSurface.withValues(alpha: 0.6),
                            ),
                          ),
                          Text(
                            "${paidPercentage.toStringAsFixed(0)}%",
                            style: TextStyle(
                              fontSize: 12,
                              fontWeight: FontWeight.bold,
                              color: Theme.of(context).colorScheme.onSurface,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 20),
          Row(
            children: [
              Expanded(
                child: _buildSmallStatCard(
                  title: UiUtils.getTranslatedLabel(context, paidKey),
                  value: UiUtils.formatAmount(
                    strVal: totalPaid.toStringAsFixed(0),
                    context: context,
                  ),
                  bgColor: StatusColors.of(
                    context,
                  ).success.withValues(alpha: 0.2),
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: _buildSmallStatCard(
                  title: UiUtils.getTranslatedLabel(context, pendingKey),
                  value: UiUtils.formatAmount(
                    strVal: totalPending.toStringAsFixed(0),
                    context: context,
                  ),
                  bgColor: StatusColors.of(
                    context,
                  ).warning.withValues(alpha: 0.2),
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          Row(
            children: [
              Expanded(
                child: _buildSmallStatCard(
                  title: UiUtils.getTranslatedLabel(context, lastPaymentKey),
                  value: lastPayment,
                  bgColor: StatusColors.of(context).info.withValues(alpha: 0.1),
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: _buildSmallStatCard(
                  title: UiUtils.getTranslatedLabel(context, nextDueKey),
                  value: nextDue,
                  bgColor: StatusColors.of(
                    context,
                  ).danger.withValues(alpha: 0.1),
                ),
              ),
            ],
          ),
        ],
      ),
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
          const SizedBox(height: 5),
          Text(
            value,
            style: TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.w600,
              color: Theme.of(context).colorScheme.onSurface,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildInstalmentSummary(FeesReport data) {
    final instalments = data.instalments;
    final total = instalments.total;
    final completed = instalments.completed;
    final pending = instalments.pending;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
          child: Text(
            UiUtils.getTranslatedLabel(context, instalmentSummaryKey),
            style: TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.w600,
              color: Theme.of(context).colorScheme.onSurface,
            ),
          ),
        ),
        Container(
          margin: const EdgeInsets.symmetric(horizontal: 20),
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
                width: 100,
                height: 100,
                child: Stack(
                  children: [
                    PieChart(
                      PieChartData(
                        sectionsSpace: 0,
                        centerSpaceRadius: 40,
                        startDegreeOffset: -90,
                        sections: [
                          PieChartSectionData(
                            color: StatusColors.of(context).success,
                            value: completed.toDouble(),
                            title: '',
                            radius: 10,
                          ),
                          PieChartSectionData(
                            color: StatusColors.of(context).warning,
                            value: pending.toDouble(),
                            title: '',
                            radius: 10,
                          ),
                        ],
                      ),
                    ),
                    Center(
                      child: Column(
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
                            "$total",
                            style: TextStyle(
                              fontSize: 16,
                              fontWeight: FontWeight.bold,
                              color: Theme.of(context).colorScheme.onSurface,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 30),
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Container(
                        width: 8,
                        height: 8,
                        decoration: BoxDecoration(
                          color: StatusColors.of(context).success,
                          shape: BoxShape.circle,
                        ),
                      ),
                      const SizedBox(width: 10),
                      Text(
                        "${UiUtils.getTranslatedLabel(context, completedKey)} : $completed",
                        style: TextStyle(
                          fontSize: 14,
                          color: Theme.of(
                            context,
                          ).colorScheme.onSurface.withValues(alpha: 0.8),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 10),
                  Row(
                    children: [
                      Container(
                        width: 8,
                        height: 8,
                        decoration: BoxDecoration(
                          color: StatusColors.of(context).warning,
                          shape: BoxShape.circle,
                        ),
                      ),
                      const SizedBox(width: 10),
                      Text(
                        "${UiUtils.getTranslatedLabel(context, pendingKey)} : $pending",
                        style: TextStyle(
                          fontSize: 14,
                          color: Theme.of(
                            context,
                          ).colorScheme.onSurface.withValues(alpha: 0.8),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildTransactionsSection() {
    return BlocBuilder<FeesTransactionsCubit, FeesTransactionState>(
      builder: (context, state) {
        if (state is FeesTransactionFetchSuccess &&
            state.feesTransactions.isEmpty) {
          return const SizedBox.shrink();
        }

        final titleWidget = Padding(
          padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 15),
          child: Text(
            UiUtils.getTranslatedLabel(context, feeTransactionSummaryKey),
            style: TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.bold,
              color: Theme.of(context).colorScheme.onSurface,
            ),
          ),
        );

        if (state is FeesTransactionFetchInProgress ||
            state is FeesTransactionInitial) {
          return Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              titleWidget,
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 20),
                child: Column(
                  children: List.generate(
                    3,
                    (index) => const Padding(
                      padding: EdgeInsets.only(bottom: 10),
                      child: ShimmerLoadingContainer(
                        child: CustomShimmerContainer(
                          height: 100,
                          borderRadius: 10,
                        ),
                      ),
                    ),
                  ),
                ),
              ),
            ],
          );
        }

        if (state is FeesTransactionFetchFailure) {
          return Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              titleWidget,
              Center(
                child: Padding(
                  padding: const EdgeInsets.all(20),
                  child: ErrorContainer(
                    errorMessageCode: state.errorMessage,
                    onTapRetry: () {
                      context
                          .read<FeesTransactionsCubit>()
                          .fetchFeesTransactions(
                            page: 1,
                            isStudentLoggedIn: !context
                                .read<AuthCubit>()
                                .isParent(),
                            childId: widget.childId,
                            sessionYearId: _selectedSessionYear?.id,
                          );
                    },
                  ),
                ),
              ),
            ],
          );
        }

        if (state is FeesTransactionFetchSuccess) {
          return Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              titleWidget,
              ListView.builder(
                shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
                itemCount: state.feesTransactions.length,
                padding: EdgeInsets.zero,
                itemBuilder: (context, index) {
                  return FeeTransactionItemContainer(
                    transaction: state.feesTransactions[index],
                    padding: EdgeInsets.symmetric(horizontal: 20, vertical: 8),
                  );
                },
              ),
              if (state.moreTransactionsFetchInProgress)
                const Padding(
                  padding: EdgeInsets.symmetric(vertical: 10),
                  child: Center(child: CircularProgressIndicator()),
                ),
              if (state.moreTransactionsFetchError &&
                  !state.moreTransactionsFetchInProgress)
                LoadMoreErrorWidget(
                  onTapRetry: () {
                    context
                        .read<FeesTransactionsCubit>()
                        .fetchMoreFeesTransactions(
                          isStudentLoggedIn: !context
                              .read<AuthCubit>()
                              .isParent(),
                          childId: widget.childId,
                          sessionYearId: _selectedSessionYear?.id,
                        );
                  },
                ),
            ],
          );
        }
        return const SizedBox.shrink();
      },
    );
  }

  Widget _buildContentShimmer() {
    return Center(
      child: ShimmerLoadingContainer(
        child: Column(
          children: [
            const SizedBox(height: 20),
            CustomShimmerContainer(
              height: 350,
              width: MediaQuery.sizeOf(context).width * 0.9,
            ),
            const SizedBox(height: 20),
            CustomShimmerContainer(
              height: 150,
              width: MediaQuery.sizeOf(context).width * 0.9,
            ),
            const SizedBox(height: 20),
            CustomShimmerContainer(
              height: 100,
              width: MediaQuery.sizeOf(context).width * 0.9,
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
      physics: const AlwaysScrollableScrollPhysics(),
      padding: EdgeInsets.only(
        top: 0,
        bottom: context.read<AuthCubit>().isParent()
            ? 20
            : UiUtils.getScrollViewBottomPadding(context) + 20,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          _buildSessionYearFilter(),
          BlocBuilder<FeesReportCubit, FeesReportState>(
            builder: (context, state) {
              if (state is FeesReportFetchInProgress ||
                  state is FeesReportInitial) {
                return _buildContentShimmer();
              }
              if (state is FeesReportFetchFailure) {
                return Center(
                  child: ErrorContainer(
                    errorMessageCode: state.errorMessage,
                    onTapRetry: _fetchData,
                  ),
                );
              }
              if (state is FeesReportFetchSuccess) {
                return Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    _buildTopStatisticsCard(state.feesData),
                    if (state.feesData.instalments.total > 0) ...[
                      _buildInstalmentSummary(state.feesData),
                    ],
                    _buildTransactionsSection(),
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
}
