import 'package:eschool_teacher/app/routes.dart';
import 'package:eschool_teacher/cubits/appConfigurationCubit.dart';
import 'package:eschool_teacher/cubits/leavesCubit.dart';
import 'package:eschool_teacher/cubits/sessionYearCubit.dart';
import 'package:eschool_teacher/data/models/leave.dart';
import 'package:eschool_teacher/data/models/sessionYear.dart';
import 'package:eschool_teacher/data/repositories/leaveRepository.dart';
import 'package:eschool_teacher/data/repositories/systemInfoRepository.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_teacher/ui/screens/leave/widgets/leaveContainer.dart';
import 'package:eschool_teacher/ui/screens/leave/widgets/monthPickerBottomsheetContainer.dart';
import 'package:eschool_teacher/ui/screens/leave/widgets/sessionYearPickerBottomsheetContainer.dart';
import 'package:eschool_teacher/ui/widgets/customAppbar.dart';
import 'package:eschool_teacher/ui/widgets/noDataContainer.dart';
import 'package:eschool_teacher/utils/labelKeys.dart';
import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

class ManageLeavesScreen extends StatefulWidget {
  const ManageLeavesScreen({super.key});

  @override
  State<ManageLeavesScreen> createState() => _ManageLeavesScreenState();

  static Route route(RouteSettings routeSettings) {
    return AdaptivePageRoute(
      builder: (_) => MultiBlocProvider(
        providers: [
          BlocProvider<LeaveCubit>(
            create: (context) => LeaveCubit(LeaveRepository()),
          ),
          BlocProvider<SessionYearCubit>(
            create: (context) => SessionYearCubit(SystemRepository()),
          ),
        ],
        child: const ManageLeavesScreen(),
      ),
    );
  }
}

class _ManageLeavesScreenState extends State<ManageLeavesScreen> {
  SessionYear? currentSelectedSessionYear;
  Month currentSelectedMonth = getCurrentMonth();

  @override
  void initState() {
    Future.delayed(Duration.zero, () {
      if (context.mounted) {
        context.read<SessionYearCubit>().fetchSessionYears();
      }
    });
    super.initState();
  }

  void fetchAllLeaves() {
    if (context.read<SessionYearCubit>().state is SessionYearFetchSuccess) {
      context.read<LeaveCubit>().fetchLeaves(
        sessionYearId: currentSelectedSessionYear?.id ?? 0,
        monthNumber: currentSelectedMonth.monthNumber!,
      );
    }
  }

  Widget _buildAppbar() {
    return Align(
      alignment: Alignment.topCenter,
      child: CustomAppBar(
        title: UiUtils.getTranslatedLabel(context, manageLeavesKey),
      ),
    );
  }

  Widget _buildPageLoader() {
    return SingleChildScrollView(
      child: Column(
        children: [
          const SizedBox(height: 15),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceEvenly,
            children: List.generate(
              2,
              (index) => const Expanded(
                child: ShimmerLoadingContainer(
                  child: CustomShimmerContainer(
                    height: 70,
                    borderRadius: 8,
                    margin: EdgeInsets.all(15),
                  ),
                ),
              ),
            ),
          ),
          Column(
            children: List.generate(
              7,
              (index) => const ShimmerLoadingContainer(
                child: CustomShimmerContainer(
                  height: 150,
                  borderRadius: 4,
                  margin: EdgeInsets.all(15),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSessionYearMonthSelectors() {
    return SizedBox(
      height: 50,
      child: BlocConsumer<SessionYearCubit, SessionYearState>(
        listener: (context, state) {
          if (state is SessionYearFetchSuccess) {
            setState(() {
              currentSelectedSessionYear = state.sessionYearList.firstWhere(
                (element) =>
                    element.id ==
                    context
                        .read<AppConfigurationCubit>()
                        .getAppConfiguration()
                        .sessionYear
                        .id,
                orElse: () {
                  return state.sessionYearList.first;
                },
              );
            });
            fetchAllLeaves();
          }
        },
        builder: (context, state) {
          if (state is SessionYearFetchInProgress) {
            return Row(
              mainAxisAlignment: MainAxisAlignment.spaceEvenly,
              children: List.generate(
                2,
                (index) => const Expanded(
                  child: ShimmerLoadingContainer(
                    child: CustomShimmerContainer(
                      height: 50,
                      borderRadius: 8,
                      margin: EdgeInsets.all(15),
                    ),
                  ),
                ),
              ),
            );
          }
          return Row(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              const SizedBox(width: 15),
              Expanded(
                child: CustomDropdownButtonContainer(
                  selectedValue: currentSelectedSessionYear?.name ?? '',
                  onTap: () async {
                    if (state is SessionYearFetchSuccess) {
                      final SessionYear? selectedSessionYear =
                          await UiUtils.showBottomSheet(
                            child: SessionYearPickerBottomsheetContainer(
                              selectedSessionYear:
                                  currentSelectedSessionYear ??
                                  state.sessionYearList.first,
                              sessionYearList: state.sessionYearList,
                            ),
                            context: context,
                          );
                      if (selectedSessionYear != null &&
                          selectedSessionYear != currentSelectedSessionYear) {
                        setState(() {
                          currentSelectedSessionYear = selectedSessionYear;
                        });
                        fetchAllLeaves();
                      }
                    }
                  },
                ),
              ),
              const SizedBox(width: 15),
              Expanded(
                child: CustomDropdownButtonContainer(
                  selectedValue: UiUtils.getTranslatedLabel(
                    context,
                    currentSelectedMonth.nameKey,
                  ),
                  onTap: () async {
                    if (context.read<SessionYearCubit>().state
                        is SessionYearFetchSuccess) {
                      final Month? selectedMonth =
                          await UiUtils.showBottomSheet(
                            child: MonthPickerBottomsheetContainer(
                              selectedMonth: currentSelectedMonth,
                              monthList: getAllMonths()
                                ..removeWhere(
                                  (element) => element.monthNumber == null,
                                ),
                            ), //to remove all months where month number is null
                            context: context,
                          );
                      if (selectedMonth != null &&
                          selectedMonth != currentSelectedMonth) {
                        setState(() {
                          currentSelectedMonth = selectedMonth;
                        });
                        fetchAllLeaves();
                      }
                    }
                  },
                ),
              ),
              const SizedBox(width: 15),
            ],
          );
        },
      ),
    );
  }

  Widget _totalContainer({required String titleKey, required double total}) {
    return Container(
      decoration: BoxDecoration(
        color: Theme.of(
          context,
        ).colorScheme.surfaceContainerLowest.withValues(alpha: 0.8),
        borderRadius: BorderRadius.circular(10),
      ),
      padding: const EdgeInsets.symmetric(vertical: 10, horizontal: 15),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            total.toString().endsWith('.0')
                ? total.toStringAsFixed(0).padLeft(2, '0')
                : total.toStringAsFixed(1),
            style: TextStyle(
              fontSize: 22,
              color: Theme.of(context).colorScheme.onSurface,
              fontWeight: FontWeight.w600,
            ),
          ),
          const SizedBox(height: 5),
          Text(
            UiUtils.getTranslatedLabel(context, titleKey),
            style: TextStyle(
              fontSize: 14,
              color: Theme.of(
                context,
              ).colorScheme.onSurface.withValues(alpha: 0.7),
              fontWeight: FontWeight.w600,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildLeaveListWithTotals({required LeaveFetchSuccess data}) {
    return Column(
      children: [
        const SizedBox(height: 15),
        Container(
          padding: EdgeInsets.symmetric(
            horizontal: UiUtils.screenContentHorizontalPadding,
            vertical: 15,
          ),
          decoration: BoxDecoration(
            color: Theme.of(
              context,
            ).colorScheme.onSurface.withValues(alpha: 0.06),
          ),
          child: Row(
            children: [
              Expanded(
                child: _totalContainer(
                  titleKey: allowedLeavesKey,
                  total: data.monthlyAllowedLeaves,
                ),
              ),
              const SizedBox(width: 15),
              Expanded(
                child: _totalContainer(
                  titleKey: leavesTakenKey,
                  total: data.leavesTaken,
                ),
              ),
            ],
          ),
        ),
        Expanded(
          child: SingleChildScrollView(
            padding: EdgeInsets.symmetric(
              horizontal: UiUtils.screenContentHorizontalPadding,
              vertical: 30,
            ),
            child: Column(
              children: [
                Container(
                  decoration: BoxDecoration(
                    color: Theme.of(context).colorScheme.surfaceContainerLow,
                    borderRadius: BorderRadius.circular(4),
                  ),
                  padding: const EdgeInsets.symmetric(vertical: 10),
                  child: Row(
                    children: [
                      Expanded(
                        child: Text(
                          '#',
                          textAlign: TextAlign.center,
                          style: TextStyle(
                            fontSize: 14,
                            color: Theme.of(
                              context,
                            ).colorScheme.onSurface.withValues(alpha: 0.7),
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ),
                      Expanded(
                        flex: 3,
                        child: Text(
                          UiUtils.getTranslatedLabel(context, leaveDateKey),
                          textAlign: TextAlign.start,
                          style: TextStyle(
                            fontSize: 14,
                            color: Theme.of(
                              context,
                            ).colorScheme.onSurface.withValues(alpha: 0.7),
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ),
                      Expanded(
                        flex: 2,
                        child: Text(
                          UiUtils.getTranslatedLabel(context, statusKey),
                          textAlign: TextAlign.center,
                          style: TextStyle(
                            fontSize: 14,
                            color: Theme.of(
                              context,
                            ).colorScheme.onSurface.withValues(alpha: 0.7),
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 15),
                ...List.generate(
                  data.leaveList.length,
                  (index) => LeaveContainer(
                    leave: data.leaveList[index],
                    index: index,
                  ),
                ),
              ],
            ),
          ),
        ),
      ],
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      floatingActionButton: FloatingActionAddButton(
        onTap: () {
          Navigator.of(context).pushNamed<bool?>(Routes.addLeave).then((value) {
            if (value == true) {
              fetchAllLeaves();
            }
          });
        },
      ),
      body: Stack(
        children: [
          Padding(
            padding: EdgeInsets.only(
              top: UiUtils.getScrollViewTopPadding(
                context: context,
                appBarHeightPercentage: UiUtils.appBarSmallerHeightPercentage,
              ),
            ),
            child: BlocBuilder<SessionYearCubit, SessionYearState>(
              builder: (context, state) {
                if (state is SessionYearFetchFailure) {
                  return Center(
                    child: ErrorContainer(
                      errorMessageCode: state.errorMessage,
                      onTapRetry: () {
                        context.read<SessionYearCubit>().fetchSessionYears();
                      },
                    ),
                  );
                }
                return Column(
                  children: [
                    _buildSessionYearMonthSelectors(),
                    Expanded(
                      child: BlocBuilder<LeaveCubit, LeaveState>(
                        builder: (context, state) {
                          if (state is LeaveFetchSuccess) {
                            return Column(
                              children: [
                                if (state.leaveList.isEmpty) ...[
                                  const Expanded(
                                    child: NoDataContainer(
                                      titleKey: noLeavesFoundKey,
                                      subtitleKey:
                                          submitLeaveRequestsEasilyAndKeepYourAdministrationInformationKey,
                                    ),
                                  ),
                                ] else ...[
                                  Expanded(
                                    child: _buildLeaveListWithTotals(
                                      data: state,
                                    ),
                                  ),
                                ],
                              ],
                            );
                          }
                          if (state is LeaveFetchFailure) {
                            return Center(
                              child: ErrorContainer(
                                errorMessageCode: state.errorMessage,
                                onTapRetry: () {
                                  fetchAllLeaves();
                                },
                              ),
                            );
                          }
                          return _buildPageLoader();
                        },
                      ),
                    ),
                  ],
                );
              },
            ),
          ),
          _buildAppbar(),
        ],
      ),
    );
  }
}
