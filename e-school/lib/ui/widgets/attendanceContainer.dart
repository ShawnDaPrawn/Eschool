import 'package:eschool/cubits/appConfigurationCubit.dart';
import 'package:eschool/cubits/attendanceCubit.dart';
import 'package:eschool/cubits/authCubit.dart';
import 'package:eschool/data/models/attendanceDay.dart';
import 'package:eschool/ui/widgets/changeCalendarMonthButton.dart';
import 'package:eschool/ui/widgets/customBackButton.dart';
import 'package:eschool/ui/widgets/errorContainer.dart';
import 'package:eschool/ui/widgets/screenTopBackgroundContainer.dart';
import 'package:eschool/ui/widgets/attendanceSummaryContainer.dart';
import 'package:eschool/ui/widgets/attendanceShimmer.dart';
import 'package:eschool/ui/widgets/totalAttendanceContainer.dart';
import 'package:eschool/utils/extensions.dart';
import 'package:eschool/utils/labelKeys.dart';
import 'package:eschool/utils/uiUtils.dart';
import 'package:eschool/ui/styles/colors.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:table_calendar/table_calendar.dart';

class AttendanceContainer extends StatefulWidget {
  const AttendanceContainer({super.key, this.childId});
  final int? childId;

  @override
  State<AttendanceContainer> createState() => _AttendanceContainerState();
}

class _AttendanceContainerState extends State<AttendanceContainer> {
  PageController? calendarPageController;

  late DateTime firstDay;
  late DateTime lastDay;
  late DateTime focusedDay;

  @override
  void initState() {
    super.initState();
    firstDay = context
        .read<AppConfigurationCubit>()
        .getAppConfiguration()
        .sessionYear
        .startDate;
    lastDay = context
        .read<AppConfigurationCubit>()
        .getAppConfiguration()
        .sessionYear
        .endDate;
    focusedDay = DateTime.now();

    if (!UiUtils.isTodayInSessionYear(firstDay, lastDay)) {
      focusedDay = firstDay;
    }

    Future.delayed(Duration.zero, () {
      if (context.mounted) {
        fetchAttendance();
      }
    });
  }

  void fetchAttendance() {
    context.read<AttendanceCubit>().fetchAttendance(
      month: focusedDay.month,
      year: focusedDay.year,
      useParentApi: context.read<AuthCubit>().isParent(),
      childId: widget.childId,
    );
  }

  Widget _buildAppBar() {
    return ScreenTopBackgroundContainer(
      heightPercentage: UiUtils.appBarSmallerHeightPercentage,
      child: Stack(
        clipBehavior: Clip.none,
        children: [
          context.read<AuthCubit>().isParent()
              ? const CustomBackButton()
              : const SizedBox(),
          Align(
            alignment: Alignment.topCenter,
            child: Text(
              UiUtils.getTranslatedLabel(context, attendanceKey),
              style: TextStyle(
                color: Theme.of(context).scaffoldBackgroundColor,
                fontSize: UiUtils.screenTitleFontSize,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Color _getColorForAttendanceType(AttendanceType type) {
    switch (type) {
      case AttendanceType.fullDayPresent:
        return palletGreen;
      case AttendanceType.fullDayAbsent:
        return palletRed;
      case AttendanceType.firstHalfAbsent:
        return palletBlue;
      case AttendanceType.secondHalfAbsent:
        return palletYellow;
    }
  }

  Widget _buildCalendarContainer(AttendanceReport report) {
    return TableCalendar(
      headerVisible: false,
      daysOfWeekHeight: 40,
      onPageChanged: (focused) {
        setState(() {
          focusedDay = focused;
        });
        fetchAttendance();
      },
      onCalendarCreated: (controller) {
        calendarPageController = controller;
      },
      calendarBuilders: CalendarBuilders(
        defaultBuilder: (context, day, focusedDay) {
          return _buildCalendarDay(day, report, false);
        },
        todayBuilder: (context, day, focusedDay) {
          return _buildCalendarDay(day, report, true);
        },
      ),
      availableGestures: AvailableGestures.none,
      daysOfWeekStyle: DaysOfWeekStyle(
        weekendStyle: TextStyle(
          color: Theme.of(context).colorScheme.primary,
          fontWeight: FontWeight.bold,
        ),
        weekdayStyle: TextStyle(
          color: Theme.of(context).colorScheme.primary,
          fontWeight: FontWeight.bold,
        ),
      ),
      headerStyle: const HeaderStyle(
        titleCentered: true,
        formatButtonVisible: false,
      ),
      firstDay: firstDay,
      lastDay: lastDay,
      focusedDay: focusedDay,
    );
  }

  Widget _buildCalendarDay(
    DateTime day,
    AttendanceReport report,
    bool isToday,
  ) {
    AttendanceDay? matchingDay;
    try {
      matchingDay = report.attendance.firstWhere(
        (element) => element.date.isSameAs(day),
      );
    } catch (_) {}
    return Container(
      margin: const EdgeInsets.all(6),
      alignment: Alignment.center,
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        color:
            matchingDay != null &&
                matchingDay.type != AttendanceType.fullDayPresent
            ? _getColorForAttendanceType(matchingDay.type)
            : isToday
            ? Theme.of(context).colorScheme.primary
            : Colors.transparent,
      ),
      child: Text(
        '${day.day}',
        style: TextStyle(
          color:
              (matchingDay != null &&
                      matchingDay.type != AttendanceType.fullDayPresent) ||
                  isToday
              ? Colors.white
              : Theme.of(context).colorScheme.secondary,
        ),
      ),
    );
  }

  Widget _buildLegend() {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16),
      child: Wrap(
        spacing: 16,
        runSpacing: 16,
        children: [
          _buildLegendItem(Theme.of(context).colorScheme.primary, todayKey),
          _buildLegendItem(palletRed, fullDayAbsentKey),
          _buildLegendItem(palletBlue, firstHalfAbsentKey),
          _buildLegendItem(palletYellow, secondHalfAbsentKey),
        ],
      ),
    );
  }

  Widget _buildLegendItem(Color color, String labelKey) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Container(
          width: 8,
          height: 8,
          decoration: BoxDecoration(shape: BoxShape.circle, color: color),
        ),
        const SizedBox(width: 5),
        Text(
          UiUtils.getTranslatedLabel(context, labelKey),
          style: TextStyle(
            fontSize: 12,
            color: Theme.of(context).colorScheme.secondary,
          ),
        ),
      ],
    );
  }

  Widget _buildAttendanceContent(AttendanceReport report) {
    return Column(
      children: [
        Container(
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(12),
            boxShadow: [
              BoxShadow(
                color: Theme.of(
                  context,
                ).colorScheme.secondary.withValues(alpha: 0.075),
                offset: const Offset(2.5, 2.5),
                blurRadius: 8,
                spreadRadius: 2,
              ),
            ],
            color: Theme.of(context).scaffoldBackgroundColor,
          ),
          width: MediaQuery.sizeOf(context).width * 0.85,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const SizedBox(height: 8),
              SizedBox(
                height: 50,
                child: Stack(
                  children: [
                    Align(
                      child: Text(
                        '${UiUtils.getMonthName(focusedDay.month)} ${focusedDay.year}',
                        style: TextStyle(
                          color: Theme.of(context).colorScheme.secondary,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ),
                    Align(
                      alignment: AlignmentDirectional.centerStart,
                      child: ChangeCalendarMonthButton(
                        onTap: () {
                          if (context.read<AttendanceCubit>().state
                              is AttendanceFetchInProgress) {
                            return;
                          }
                          calendarPageController?.previousPage(
                            duration: const Duration(milliseconds: 400),
                            curve: Curves.easeInOut,
                          );
                        },
                        isDisable:
                            focusedDay.month == firstDay.month &&
                            focusedDay.year == firstDay.year,
                        isNextButton: false,
                      ),
                    ),
                    Align(
                      alignment: AlignmentDirectional.centerEnd,
                      child: ChangeCalendarMonthButton(
                        onTap: () {
                          if (context.read<AttendanceCubit>().state
                              is AttendanceFetchInProgress) {
                            return;
                          }
                          calendarPageController?.nextPage(
                            duration: const Duration(milliseconds: 400),
                            curve: Curves.easeInOut,
                          );
                        },
                        isDisable:
                            focusedDay.month == lastDay.month &&
                            focusedDay.year == lastDay.year,
                        isNextButton: true,
                      ),
                    ),
                  ],
                ),
              ),
              Padding(
                padding: const EdgeInsets.symmetric(
                  horizontal: 10,
                  vertical: 8,
                ),
                child: _buildCalendarContainer(report),
              ),
              _buildLegend(),
              const SizedBox(height: 16),
            ],
          ),
        ),
        const SizedBox(height: 20),
        TotalAttendanceContainer(
          totalPresent: report.totalPresent,
          totalAbsent: report.totalAbsent,
        ),
        const SizedBox(height: 16),
        GridView.count(
          crossAxisCount: 2,
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          padding: EdgeInsets.zero,
          crossAxisSpacing: 15,
          mainAxisSpacing: 15,
          childAspectRatio: 1.2,
          children: [
            AttendanceSummaryContainer(
              titleKey: fullDayPresentKey,
              value: report.fullDayPresent.toString(),
              backgroundColor: palletGreen,
            ),
            AttendanceSummaryContainer(
              titleKey: fullDayAbsentKey,
              value: report.fullDayAbsent.toString(),
              backgroundColor: palletRed,
            ),
            AttendanceSummaryContainer(
              titleKey: firstHalfAbsentKey,
              value: report.firstHalfAbsent.toString(),
              backgroundColor: palletBlue,
            ),
            AttendanceSummaryContainer(
              titleKey: secondHalfAbsentKey,
              value: report.secondHalfAbsent.toString(),
              backgroundColor: palletYellow,
            ),
          ],
        ),
      ],
    );
  }

  @override
  Widget build(BuildContext context) {
    return Stack(
      children: [
        BlocBuilder<AttendanceCubit, AttendanceState>(
          builder: (context, state) {
            if (state is AttendanceFetchSuccess) {
              return SingleChildScrollView(
                padding: EdgeInsets.only(
                  left:
                      MediaQuery.sizeOf(context).width *
                      UiUtils.screenContentHorizontalPaddingInPercentage,
                  right:
                      MediaQuery.sizeOf(context).width *
                      UiUtils.screenContentHorizontalPaddingInPercentage,
                  bottom: UiUtils.getScrollViewBottomPadding(context),
                  top: UiUtils.getScrollViewTopPadding(
                    context: context,
                    appBarHeightPercentage:
                        UiUtils.appBarSmallerHeightPercentage,
                  ),
                ),
                child: _buildAttendanceContent(state.attendanceReport),
              );
            } else if (state is AttendanceFetchFailure) {
              return Center(
                child: ErrorContainer(
                  errorMessageCode: state.errorMessage,
                  onTapRetry: () {
                    fetchAttendance();
                  },
                ),
              );
            }

            // Loading or Initial state
            return SingleChildScrollView(
              padding: EdgeInsets.only(
                left:
                    MediaQuery.sizeOf(context).width *
                    UiUtils.screenContentHorizontalPaddingInPercentage,
                right:
                    MediaQuery.sizeOf(context).width *
                    UiUtils.screenContentHorizontalPaddingInPercentage,
                bottom: UiUtils.getScrollViewBottomPadding(context),
                top: UiUtils.getScrollViewTopPadding(
                  context: context,
                  appBarHeightPercentage: UiUtils.appBarSmallerHeightPercentage,
                ),
              ),
              child: Column(
                children: [
                  Container(
                    decoration: BoxDecoration(
                      borderRadius: const BorderRadius.only(
                        topLeft: Radius.circular(12),
                        topRight: Radius.circular(12),
                      ),
                      boxShadow: [
                        BoxShadow(
                          color: Theme.of(
                            context,
                          ).colorScheme.secondary.withValues(alpha: 0.075),
                          offset: const Offset(2.5, 2.5),
                          blurRadius: 8,
                          spreadRadius: 2,
                        ),
                      ],
                      color: Theme.of(context).scaffoldBackgroundColor,
                    ),
                    width: MediaQuery.sizeOf(context).width * 0.85,
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const SizedBox(height: 8),
                        SizedBox(
                          height: 50,
                          child: Stack(
                            children: [
                              Align(
                                child: Text(
                                  '${UiUtils.getMonthName(focusedDay.month)} ${focusedDay.year}',
                                  style: TextStyle(
                                    color: Theme.of(
                                      context,
                                    ).colorScheme.secondary,
                                    fontWeight: FontWeight.w600,
                                  ),
                                ),
                              ),
                              Align(
                                alignment: AlignmentDirectional.centerStart,
                                child: ChangeCalendarMonthButton(
                                  onTap: () {},
                                  isDisable: true,
                                  isNextButton: false,
                                ),
                              ),
                              Align(
                                alignment: AlignmentDirectional.centerEnd,
                                child: ChangeCalendarMonthButton(
                                  onTap: () {},
                                  isDisable: true,
                                  isNextButton: true,
                                ),
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(height: 8),
                      ],
                    ),
                  ),
                  const AttendanceShimmer(),
                ],
              ),
            );
          },
        ),
        Align(alignment: Alignment.topCenter, child: _buildAppBar()),
      ],
    );
  }
}
