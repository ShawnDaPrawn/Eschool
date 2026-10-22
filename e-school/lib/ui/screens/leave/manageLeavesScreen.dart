import 'package:eschool/app/routes.dart';
import 'package:eschool/cubits/authCubit.dart';
import 'package:eschool/cubits/leavesCubit.dart';
import 'package:eschool/data/models/leave.dart';
import 'package:eschool/data/models/student.dart';
import 'package:eschool/data/repositories/leaveRepository.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool/ui/screens/leave/widgets/leaveContainer.dart';
import 'package:eschool/ui/screens/leave/widgets/monthPickerBottomsheetContainer.dart';
import 'package:eschool/ui/widgets/customAppbar.dart';
import 'package:eschool/ui/widgets/noDataContainer.dart';
import 'package:eschool/ui/widgets/swapStatusFilterBottomsheetContainer.dart';
import 'package:eschool/utils/labelKeys.dart';
import 'package:eschool/utils/uiUtils.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

class ManageLeavesScreen extends StatefulWidget {
  const ManageLeavesScreen({required this.studentDetails, super.key});
  final Student studentDetails;

  @override
  State<ManageLeavesScreen> createState() => _ManageLeavesScreenState();

  static Route route(RouteSettings routeSettings) {
    return AdaptivePageRoute(
      builder: (_) => BlocProvider<LeaveCubit>(
        create: (context) => LeaveCubit(LeaveRepository()),
        child: ManageLeavesScreen(
          studentDetails: routeSettings.arguments! as Student,
        ),
      ),
    );
  }
}

class _ManageLeavesScreenState extends State<ManageLeavesScreen> {
  Month currentSelectedMonth = getCurrentMonth();
  String currentSelectedStatusFilter = UiUtils.getLeavesStatusKey(3);
  int? selectedStatusId;
  List<String> statuses = [pendingKey, acceptedKey, rejectedKey, allKey];

  @override
  void initState() {
    super.initState();
    fetchAllLeaves();
  }

  void fetchAllLeaves() {
    context.read<LeaveCubit>().fetchLeaves(
      status: selectedStatusId == 3 ? null : selectedStatusId,
      monthNumber: currentSelectedMonth.monthNumber,
      isParent: context.read<AuthCubit>().isParent(),
      childId: widget.studentDetails.id,
    );
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

  Widget _buildMonthStatusSelectors() {
    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 15),
      height: 50,
      child: Row(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Expanded(
            child: CustomDropdownButtonContainer(
              selectedValue: UiUtils.getTranslatedLabel(
                context,
                currentSelectedMonth.nameKey,
              ),
              onTap: () async {
                final Month? selectedMonth = await UiUtils.showBottomSheet(
                  child: MonthPickerBottomsheetContainer(
                    selectedMonth: currentSelectedMonth,
                    monthList: getAllMonths(),
                  ),
                  context: context,
                );
                if (selectedMonth != null &&
                    selectedMonth != currentSelectedMonth) {
                  setState(() {
                    currentSelectedMonth = selectedMonth;
                  });
                  fetchAllLeaves();
                }
              },
            ),
          ),
          const SizedBox(width: 20),
          Expanded(
            child: CustomDropdownButtonContainer(
              selectedValue: UiUtils.getTranslatedLabel(
                context,
                currentSelectedStatusFilter,
              ),
              onTap: () async {
                final String? selectedStatusFilter =
                    await UiUtils.showBottomSheet(
                      child: SwapStatusFilterBottomsheetContainer(
                        selectedStatus: currentSelectedStatusFilter,
                        statusList: statuses,
                      ),
                      context: context,
                    );
                if (selectedStatusFilter != null &&
                    selectedStatusFilter != currentSelectedStatusFilter) {
                  setState(() {
                    currentSelectedStatusFilter = selectedStatusFilter;
                    selectedStatusId = statuses.indexOf(
                      currentSelectedStatusFilter,
                    );
                  });
                  fetchAllLeaves();
                }
              },
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildLeaveListWithTotals({required LeaveFetchSuccess data}) {
    final int total = data.leaveList.length;
    return Column(
      children: [
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
                          UiUtils.getTranslatedLabel(context, leaveRequestsKey),
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
                          total.toStringAsFixed(0).padLeft(2, '0'),
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
          Navigator.of(context)
              .pushNamed<bool?>(
                Routes.addLeave,
                arguments: widget.studentDetails,
              )
              .then((value) {
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
            child: Column(
              children: [
                _buildMonthStatusSelectors(),
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
                                ),
                              ),
                            ] else ...[
                              Expanded(
                                child: _buildLeaveListWithTotals(data: state),
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
            ),
          ),
          _buildAppbar(),
        ],
      ),
    );
  }
}
