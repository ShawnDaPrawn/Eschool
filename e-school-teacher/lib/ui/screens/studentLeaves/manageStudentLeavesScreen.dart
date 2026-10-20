import 'package:eschool_teacher/cubits/dashboardCubit.dart';
import 'package:eschool_teacher/cubits/getStudentLeaveCubit.dart';
import 'package:eschool_teacher/cubits/sessionYearCubit.dart';
import 'package:eschool_teacher/cubits/updateStudentLeaveStatusCubit.dart';
import 'package:eschool_teacher/data/models/classSectionDetails.dart';
import 'package:eschool_teacher/data/models/leave.dart';
import 'package:eschool_teacher/data/models/sessionYear.dart';
import 'package:eschool_teacher/data/repositories/studentLeaveRepository.dart';
import 'package:eschool_teacher/data/repositories/systemInfoRepository.dart';
import 'package:eschool_teacher/ui/screens/leave/widgets/dropdownButtonContainer.dart';
import 'package:eschool_teacher/ui/screens/leave/widgets/monthPickerBottomsheetContainer.dart';
import 'package:eschool_teacher/ui/screens/leave/widgets/sessionYearPickerBottomsheetContainer.dart';
import 'package:eschool_teacher/ui/screens/studentLeaves/widgets/studentLeaveContainer.dart';
import 'package:eschool_teacher/ui/widgets/customAppbar.dart';
import 'package:eschool_teacher/ui/widgets/customShimmerContainer.dart';
import 'package:eschool_teacher/ui/widgets/errorContainer.dart';
import 'package:eschool_teacher/ui/widgets/myClassesBottomsheetContainer.dart';
import 'package:eschool_teacher/ui/widgets/noDataContainer.dart';
import 'package:eschool_teacher/ui/widgets/shimmerLoadingContainer.dart';
import 'package:eschool_teacher/utils/labelKeys.dart';
import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

class ManageStudentLeavesScreen extends StatefulWidget {
  const ManageStudentLeavesScreen({super.key});

  @override
  State<ManageStudentLeavesScreen> createState() =>
      ManageStudentLeavesScreenState();

  static Route route(RouteSettings routeSettings) {
    return CupertinoPageRoute(
      builder: (_) => MultiBlocProvider(
        providers: [
          BlocProvider<GetStudentLeaveCubit>(
            create: (context) => GetStudentLeaveCubit(StudentLeaveRepository()),
          ),
          BlocProvider<SessionYearCubit>(
            create: (context) => SessionYearCubit(SystemRepository()),
          ),
          BlocProvider<UpdateStudentLeaveStatusCubit>(
            create: (context) =>
                UpdateStudentLeaveStatusCubit(StudentLeaveRepository()),
          ),
        ],
        child: const ManageStudentLeavesScreen(),
      ),
    );
  }
}

class ManageStudentLeavesScreenState extends State<ManageStudentLeavesScreen> {
  SessionYear? currentSelectedSessionYear;
  ClassSectionDetails? currentSelectedClass;
  Month currentSelectedMonth = getAllMonths()[0];

  @override
  void initState() {
    Future.delayed(Duration.zero, () {
      if (context.mounted) {
        context.read<SessionYearCubit>().fetchSessionYears();
      }
    });
    super.initState();
  }

  void fetchAllStudentLeaves() {
    if (context.read<SessionYearCubit>().state is SessionYearFetchSuccess) {
      context.read<GetStudentLeaveCubit>().getStudentLeave(
        classSectionId: currentSelectedClass!.id.toString(),
        month: currentSelectedMonth.monthNumber?.toString(),
        sessionYearId: currentSelectedSessionYear?.id.toString() ?? '0',
      );
    }
  }

  Widget _buildAppbar() {
    return Align(
      alignment: Alignment.topCenter,
      child: CustomAppBar(
        title: UiUtils.getTranslatedLabel(context, manageStudentLeavesKey),
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
              currentSelectedSessionYear = state.sessionYearList.first;
            });
            fetchAllStudentLeaves();
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
                        fetchAllStudentLeaves();
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
                              monthList: getAllMonths(),
                            ),
                            context: context,
                          );
                      if (selectedMonth != null) {
                        setState(() {
                          currentSelectedMonth = selectedMonth;
                        });
                        fetchAllStudentLeaves();
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

  Widget _buildClassSelectors() {
    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 15),
      height: 50,
      child: BlocBuilder<DashboardCubit, DashboardState>(
        builder: (context, state) {
          if (state is DashboardFetchInProgress) {
            return const ShimmerLoadingContainer(
              child: CustomShimmerContainer(
                height: 100,
                borderRadius: 8,
                margin: EdgeInsets.all(15),
              ),
            );
          }
          if (state is DashboardFetchSuccess) {
            currentSelectedClass ??= state.primaryClass!.first;
            return CustomDropdownButtonContainer(
              selectedValue: currentSelectedClass!
                  .getClassSectionNameWithSemester(context: context),
              onTap: () async {
                final ClassSectionDetails? selectedClass =
                    await UiUtils.showBottomSheet(
                      child: MyClassesBottomsheetContainer(
                        selectedClass: currentSelectedClass!,
                        classesList: state.primaryClass!,
                      ),
                      context: context,
                    );
                if (selectedClass != null &&
                    selectedClass != currentSelectedClass) {
                  setState(() {
                    currentSelectedClass = selectedClass;
                  });
                  fetchAllStudentLeaves();
                }
              },
            );
          }
          return const SizedBox.shrink();
        },
      ),
    );
  }

  Widget _buildLeaveListWithTotals({required GetStudentLeaveSuccess data}) {
    return BlocListener<
      UpdateStudentLeaveStatusCubit,
      UpdateStudentLeaveStatusState
    >(
      listener: (context, state) {
        if (state is UpdateStudentLeaveStatusSuccess) {
          //removing from dashboard if accepted or rejected
          if (!state.isStatusPending) {
            context.read<DashboardCubit>().removeStudentLeaveRequest(state.id);
          }
          context.read<GetStudentLeaveCubit>().updateStatus(
            state.id,
            state.status,
          );
          UiUtils.showBottomToastOverlay(
            context: context,
            errorMessage: UiUtils.getTranslatedLabel(
              context,
              leaveRequestStatusUpdatedSuccessfullyKey,
            ),
            backgroundColor: Theme.of(context).colorScheme.onPrimary,
          );
        } else if (state is UpdateStudentLeaveStatusFailure) {
          UiUtils.showBottomToastOverlay(
            context: context,
            errorMessage: UiUtils.getErrorMessageFromErrorCode(
              context,
              state.exception,
            ),
            backgroundColor: Theme.of(context).colorScheme.error,
          );
        }
      },
      child: Column(
        children: [
          const SizedBox(height: 15),
          Expanded(
            child: RefreshIndicator(
              onRefresh: () {
                fetchAllStudentLeaves();
                return Future.value();
              },
              child: SizedBox(
                height: double.maxFinite,
                child: SingleChildScrollView(
                  padding: EdgeInsets.symmetric(
                    horizontal: UiUtils.screenContentHorizontalPadding,
                    vertical: 30,
                  ),
                  child: Column(
                    children: [
                      Container(
                        decoration: BoxDecoration(
                          color: Theme.of(context).colorScheme.surface,
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
                                  color: Theme.of(context).colorScheme.secondary
                                      .withValues(alpha: 0.7),
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                            ),
                            Expanded(
                              flex: 3,
                              child: Text(
                                UiUtils.getTranslatedLabel(
                                  context,
                                  leaveRequestsKey,
                                ),
                                textAlign: TextAlign.start,
                                style: TextStyle(
                                  fontSize: 14,
                                  color: Theme.of(context).colorScheme.secondary
                                      .withValues(alpha: 0.7),
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                            ),
                            Expanded(
                              flex: 2,
                              child: Text(
                                data.totalStudentLeaves.toString().padLeft(
                                  2,
                                  '0',
                                ),
                                textAlign: TextAlign.center,
                                style: TextStyle(
                                  fontSize: 14,
                                  color: Theme.of(context).colorScheme.secondary
                                      .withValues(alpha: 0.7),
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 15),
                      ...List.generate(
                        data.studentLeaves.length,
                        (index) => StudentLeaveContainer(
                          leave: data.studentLeaves[index],
                          index: index,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
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
                    _buildClassSelectors(),
                    const SizedBox(height: 15),
                    _buildSessionYearMonthSelectors(),
                    Expanded(
                      child:
                          BlocBuilder<
                            GetStudentLeaveCubit,
                            GetStudentLeaveState
                          >(
                            builder: (context, state) {
                              if (state is GetStudentLeaveSuccess) {
                                return Column(
                                  children: [
                                    if (state.totalStudentLeaves == 0 &&
                                        state.studentLeaves.isEmpty) ...[
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
                              if (state is GetStudentLeaveFailure) {
                                return Center(
                                  child: ErrorContainer(
                                    errorMessageCode: state.errorMessage,
                                    onTapRetry: () {
                                      fetchAllStudentLeaves();
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
