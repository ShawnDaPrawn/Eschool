import 'package:eschool_teacher/cubits/appConfigurationCubit.dart';
import 'package:eschool_teacher/cubits/teacherAttendanceCubit.dart';
import 'package:eschool_teacher/data/models/teacherAttendance.dart';
import 'package:eschool_teacher/data/repositories/teacherAttendanceRepository.dart';
import 'package:eschool_teacher/ui/screens/academicCalendar/widgets/changeCalendarMonthButton.dart';
import 'package:eschool_teacher/ui/screens/teacherAttendance/widgets/attendanceSummaryContainer.dart';
import 'package:eschool_teacher/ui/screens/teacherAttendance/widgets/teacherAttendanceShimmer.dart';
import 'package:eschool_teacher/ui/screens/teacherAttendance/widgets/totalAttendanceContainer.dart';
import 'package:eschool_teacher/ui/widgets/customBackButton.dart';
import 'package:eschool_teacher/ui/widgets/errorContainer.dart';
import 'package:eschool_teacher/ui/widgets/screenTopBackgroundContainer.dart';
import 'package:eschool_teacher/utils/extensions.dart';
import 'package:eschool_teacher/utils/labelKeys.dart';
import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:eschool_teacher/ui/styles/colors.dart';
import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:table_calendar/table_calendar.dart';

class TeacherAttendenceScreen extends StatefulWidget {
  const TeacherAttendenceScreen({super.key});

  @override
  State<TeacherAttendenceScreen> createState() =>
      _TeacherAttendenceScreenState();

  static Route route(RouteSettings routeSettings) {
    return CupertinoPageRoute(
      builder: (context) => BlocProvider<TeacherAttendanceCubit>(
        create: (context) =>
            TeacherAttendanceCubit(TeacherAttendanceRepository()),
        child: const TeacherAttendenceScreen(),
      ),
    );
  }
}

class _TeacherAttendenceScreenState extends State<TeacherAttendenceScreen> {
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

    if (!UiUtils.isToadyIsInSessionYear(firstDay, lastDay)) {
      focusedDay = firstDay;
    }

    Future.delayed(Duration.zero, () {
      if (context.mounted) {
        fetchAttendance();
      }
    });
  }

  void fetchAttendance() {
    context.read<TeacherAttendanceCubit>().fetchTeacherAttendance(
      month: focusedDay.month,
      year: focusedDay.year,
    );
  }

  Widget _buildAppBar() {
    return ScreenTopBackgroundContainer(
      heightPercentage: UiUtils.appBarSmallerHeightPercentage,
      child: Stack(
        clipBehavior: Clip.none,
        children: [
          const CustomBackButton(),
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

  Color _getColorForAttendanceType(TeacherAttendanceType type) {
    switch (type) {
      case TeacherAttendanceType.fullDayPresent:
        return palletGreen;
      case TeacherAttendanceType.fullDayAbsent:
        return palletRed;
      case TeacherAttendanceType.firstHalfAbsent:
        return palletBlue;
      case TeacherAttendanceType.secondHalfAbsent:
        return palletYellow;
    }
  }

  Widget _buildCalendarContainer(TeacherAttendanceReport report) {
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
    TeacherAttendanceReport report,
    bool isToday,
  ) {
    TeacherAttendanceDay? matchingDay;
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
                matchingDay.type != TeacherAttendanceType.fullDayPresent
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
                      matchingDay.type !=
                          TeacherAttendanceType.fullDayPresent) ||
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

  Widget _buildAttendanceContent(TeacherAttendanceReport report) {
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
                          if (context.read<TeacherAttendanceCubit>().state
                              is TeacherAttendanceFetchInProgress) {
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
                          if (context.read<TeacherAttendanceCubit>().state
                              is TeacherAttendanceFetchInProgress) {
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
    return Scaffold(
      body: Stack(
        children: [
          BlocBuilder<TeacherAttendanceCubit, TeacherAttendanceState>(
            builder: (context, state) {
              if (state is TeacherAttendanceFetchSuccess) {
                return SingleChildScrollView(
                  padding: EdgeInsets.only(
                    left:
                        MediaQuery.sizeOf(context).width *
                        UiUtils.screenContentHorizontalPaddingPercentage,
                    right:
                        MediaQuery.sizeOf(context).width *
                        UiUtils.screenContentHorizontalPaddingPercentage,
                    bottom: UiUtils.getScrollViewBottomPadding(context),
                    top: UiUtils.getScrollViewTopPadding(
                      context: context,
                      appBarHeightPercentage:
                          UiUtils.appBarSmallerHeightPercentage,
                    ),
                  ),
                  child: _buildAttendanceContent(state.report),
                );
              } else if (state is TeacherAttendanceFetchFailure) {
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
                      UiUtils.screenContentHorizontalPaddingPercentage,
                  right:
                      MediaQuery.sizeOf(context).width *
                      UiUtils.screenContentHorizontalPaddingPercentage,
                  bottom: UiUtils.getScrollViewBottomPadding(context),
                  top: UiUtils.getScrollViewTopPadding(
                    context: context,
                    appBarHeightPercentage:
                        UiUtils.appBarSmallerHeightPercentage,
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
                    const TeacherAttendanceShimmer(),
                  ],
                ),
              );
            },
          ),
          Align(alignment: Alignment.topCenter, child: _buildAppBar()),
        ],
      ),
    );
  }
}
