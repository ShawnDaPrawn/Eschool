import 'package:eschool/data/models/leave.dart';
import 'package:eschool/ui/screens/leave/widgets/dropdownButtonContainer.dart';
import 'package:eschool/ui/screens/leave/widgets/monthPickerBottomsheetContainer.dart';
import 'package:eschool/cubits/sessionYearCubit.dart';
import 'package:eschool/data/models/sessionYear.dart';
import 'package:eschool/cubits/appConfigurationCubit.dart';
import 'package:eschool/cubits/authCubit.dart';
import 'package:eschool/ui/screens/reports/widgets/sessionYearPickerBottomsheetContainer.dart';
import 'package:eschool/ui/screens/reports/models/attendanceReport.dart';
import 'package:eschool/ui/screens/reports/cubits/attendanceReportCubit.dart';
import 'package:eschool/ui/widgets/customShimmerContainer.dart';
import 'package:eschool/ui/widgets/errorContainer.dart';
import 'package:eschool/ui/widgets/shimmerLoadingContainer.dart';
import 'package:eschool/utils/labelKeys.dart';
import 'package:eschool/utils/uiUtils.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:table_calendar/table_calendar.dart';
import 'package:fl_chart/fl_chart.dart';

class AttendanceReportTab extends StatefulWidget {
  final int? childId;
  const AttendanceReportTab({super.key, this.childId});

  @override
  State<AttendanceReportTab> createState() => _AttendanceReportTabState();

  static Widget buildShimmer(BuildContext context) {
    return SingleChildScrollView(
      padding: EdgeInsets.only(
        bottom: context.read<AuthCubit>().isParent()
            ? 0
            : UiUtils.getScrollViewBottomPadding(context),
      ),
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
                height: 200,
                width: MediaQuery.sizeOf(context).width * 0.9,
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _AttendanceReportTabState extends State<AttendanceReportTab>
    with AutomaticKeepAliveClientMixin {
  SessionYear? _selectedSessionYear;
  Month _currentSelectedMonth = getAllMonths()[DateTime.now().month - 1];

  @override
  bool get wantKeepAlive => true;

  @override
  void initState() {
    super.initState();
    Future.delayed(Duration.zero, () {
      _initializeFilters();
    });
  }

  void _initializeFilters() {
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

      _fetchReport();
    }
  }

  void _fetchReport() {
    if (_selectedSessionYear == null) return;
    context.read<AttendanceReportCubit>().fetchAttendanceReport(
      sessionYearId: _selectedSessionYear!.id,
      month: _currentSelectedMonth.monthNumber,
      childId: widget.childId,
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
                  _fetchReport();
                }
              },
            ),
          ),
          const SizedBox(width: 15),
          Expanded(
            child: CustomDropdownButtonContainer(
              selectedValue: UiUtils.getTranslatedLabel(
                context,
                _currentSelectedMonth.nameKey,
              ),
              onTap: () async {
                final Month? selectedMonth = await UiUtils.showBottomSheet(
                  child: MonthPickerBottomsheetContainer(
                    selectedMonth: _currentSelectedMonth,
                    monthList: getAllMonths(),
                  ),
                  context: context,
                );
                if (selectedMonth != null &&
                    selectedMonth.monthNumber !=
                        _currentSelectedMonth.monthNumber) {
                  setState(() {
                    _currentSelectedMonth = selectedMonth;
                  });
                  _fetchReport();
                }
              },
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildCalendar(AttendanceReport data) {
    final List<AttendanceEntry> attendanceList = data.attendance;

    // Convert to a more accessible format
    final Map<int, int> statusMap = {};
    for (var item in attendanceList) {
      if (item.date.isNotEmpty) {
        final DateTime date = DateTime.parse(item.date);
        statusMap[date.day] = item.type;
      }
    }

    int calendarYear =
        _selectedSessionYear?.startDate.year ?? DateTime.now().year;
    if (_selectedSessionYear != null &&
        _selectedSessionYear!.startDate.year !=
            _selectedSessionYear!.endDate.year &&
        _currentSelectedMonth.monthNumber <
            _selectedSessionYear!.startDate.month) {
      calendarYear = _selectedSessionYear!.endDate.year;
    }

    final calendarFocusedDay = DateTime(
      calendarYear,
      _currentSelectedMonth.monthNumber,
      1,
    );

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
          Center(
            child: Text(
              "${UiUtils.getTranslatedLabel(context, _currentSelectedMonth.nameKey)} $calendarYear",
              style: TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.bold,
                color: Theme.of(context).colorScheme.onSurface,
              ),
            ),
          ),
          const SizedBox(height: 15),
          TableCalendar(
            firstDay: DateTime(calendarFocusedDay.year - 1, 1, 1),
            lastDay: DateTime(calendarFocusedDay.year + 1, 12, 31),
            focusedDay: calendarFocusedDay,
            headerVisible: false,
            daysOfWeekHeight: 40,
            availableGestures: AvailableGestures.none,
            daysOfWeekStyle: DaysOfWeekStyle(
              weekdayStyle: TextStyle(
                color: Theme.of(context).colorScheme.primary,
                fontSize: 12,
              ),
              weekendStyle: TextStyle(
                color: Theme.of(context).colorScheme.primary,
                fontSize: 12,
              ),
            ),
            calendarBuilders: CalendarBuilders(
              defaultBuilder: (context, day, focusedDay) {
                return _buildCalendarDay(day, statusMap);
              },
              todayBuilder: (context, day, focusedDay) {
                return _buildCalendarDay(day, statusMap, isToday: true);
              },
            ),
          ),
          const SizedBox(height: 15),
          Wrap(
            spacing: 15,
            runSpacing: 10,
            children: [
              _buildLegendItem(
                Theme.of(context).colorScheme.primary,
                UiUtils.getTranslatedLabel(context, todayKey),
              ),
              _buildLegendItem(
                StatusColors.of(context).danger,
                UiUtils.getTranslatedLabel(context, fullDayAbsentKey),
              ),
              _buildLegendItem(
                StatusColors.of(context).info,
                UiUtils.getTranslatedLabel(context, firstHalfAbsentKey),
              ),
              _buildLegendItem(
                StatusColors.of(context).warning,
                UiUtils.getTranslatedLabel(context, secondHalfAbsentKey),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildCalendarDay(
    DateTime day,
    Map<int, int> statusMap, {
    bool isToday = false,
  }) {
    Color? dotColor;
    Color textColor = Theme.of(
      context,
    ).colorScheme.onSurface.withValues(alpha: 0.7);

    if (statusMap.containsKey(day.day)) {
      final status = statusMap[day.day];
      if (status == 1) {
        dotColor = StatusColors.of(context).danger;
      } else if (status == 2) {
        dotColor = StatusColors.of(context).info;
      } else if (status == 3) {
        dotColor = StatusColors.of(context).warning;
      }
    }

    if (dotColor != null) {
      textColor = Theme.of(context).colorScheme.readableOn(dotColor);
    } else if (isToday) {
      dotColor = Theme.of(context).colorScheme.primary;
      textColor = Theme.of(context).colorScheme.onPrimary;
    }

    return Container(
      margin: const EdgeInsets.all(6),
      decoration: BoxDecoration(
        color: dotColor ?? Colors.transparent,
        shape: BoxShape.circle,
      ),
      child: Stack(
        alignment: Alignment.center,
        children: [
          Center(
            child: Text(
              '${day.day}',
              style: TextStyle(color: textColor, fontSize: 14),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildLegendItem(Color color, String text) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Container(
          width: 8,
          height: 8,
          decoration: BoxDecoration(color: color, shape: BoxShape.circle),
        ),
        const SizedBox(width: 5),
        Text(
          text,
          style: TextStyle(
            fontSize: 11,
            color: Theme.of(
              context,
            ).colorScheme.onSurface.withValues(alpha: 0.8),
          ),
        ),
      ],
    );
  }

  Widget _buildSummaryCards(AttendanceReport data) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
      child: Row(
        children: [
          Expanded(
            child: _buildSummaryCard(
              title: UiUtils.getTranslatedLabel(context, totalPresentDayKey),
              value: data.totalPresent.toString(),
              bgColor: StatusColors.of(context).success.withValues(alpha: 0.2),
            ),
          ),
          const SizedBox(width: 15),
          Expanded(
            child: _buildSummaryCard(
              title: UiUtils.getTranslatedLabel(context, totalAbsentDayKey),
              value: data.totalAbsent.toString(),
              bgColor: StatusColors.of(context).danger.withValues(alpha: 0.2),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSummaryCard({
    required String title,
    required String value,
    required Color bgColor,
  }) {
    return Container(
      padding: const EdgeInsets.all(15),
      decoration: BoxDecoration(
        color: bgColor,
        borderRadius: BorderRadius.circular(8),
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
              ).colorScheme.onSurface.withValues(alpha: 0.7),
            ),
          ),
          const SizedBox(height: 5),
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

  Widget _buildChart(AttendanceReport data) {
    final int total =
        data.fullDayPresent +
        data.fullDayAbsent +
        data.firstHalfAbsent +
        data.secondHalfAbsent;
    final double fullDayPresent = total > 0
        ? (data.fullDayPresent / total) * 100
        : 0;
    final double fullDayAbsent = total > 0
        ? (data.fullDayAbsent / total) * 100
        : 0;
    final double firstHalfAbsent = total > 0
        ? (data.firstHalfAbsent / total) * 100
        : 0;
    final double secondHalfAbsent = total > 0
        ? (data.secondHalfAbsent / total) * 100
        : 0;

    String getFormattedLable(String text) {
      return text.split(' ').length >= 3
          ? text.replaceFirstMapped(
              RegExp(r'(\S+\s+\S+)\s'),
              (match) => '${match.group(1)}\n',
            )
          : text;
    }

    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 20, vertical: 15),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            UiUtils.getTranslatedLabel(context, attendanceSummaryKey),
            style: TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.bold,
              color: Theme.of(context).colorScheme.onSurface,
            ),
          ),
          const SizedBox(height: 24),
          SizedBox(
            height: 200,
            child: BarChart(
              BarChartData(
                alignment: BarChartAlignment.spaceAround,
                maxY: 100,
                barTouchData: BarTouchData(enabled: false),
                titlesData: FlTitlesData(
                  show: true,
                  bottomTitles: AxisTitles(
                    sideTitles: SideTitles(
                      showTitles: true,
                      getTitlesWidget: (value, meta) {
                        String text = '';
                        switch (value.toInt()) {
                          case 0:
                            text = getFormattedLable(
                              UiUtils.getTranslatedLabel(
                                context,
                                fullDayPresentKey,
                              ),
                            );
                            break;
                          case 1:
                            text = getFormattedLable(
                              UiUtils.getTranslatedLabel(
                                context,
                                fullDayAbsentKey,
                              ),
                            );
                            break;
                          case 2:
                            text = getFormattedLable(
                              UiUtils.getTranslatedLabel(
                                context,
                                firstHalfAbsentKey,
                              ),
                            );
                            break;
                          case 3:
                            text = getFormattedLable(
                              UiUtils.getTranslatedLabel(
                                context,
                                secondHalfAbsentKey,
                              ),
                            );
                            break;
                        }
                        return Padding(
                          padding: const EdgeInsets.only(top: 8.0),
                          child: Text(
                            text,
                            textAlign: TextAlign.center,
                            style: TextStyle(
                              color: Theme.of(
                                context,
                              ).colorScheme.onSurface.withValues(alpha: 0.7),
                              fontSize: 10,
                            ),
                          ),
                        );
                      },
                      reservedSize: 35,
                    ),
                  ),
                  leftTitles: AxisTitles(
                    sideTitles: SideTitles(
                      showTitles: true,
                      reservedSize: 30,
                      interval: 20,
                      getTitlesWidget: (value, meta) {
                        return Text(
                          value.toInt().toString(),
                          style: TextStyle(
                            color: Theme.of(
                              context,
                            ).colorScheme.onSurface.withValues(alpha: 0.7),
                            fontSize: 12,
                          ),
                        );
                      },
                    ),
                  ),
                  topTitles: const AxisTitles(
                    sideTitles: SideTitles(showTitles: false),
                  ),
                  rightTitles: const AxisTitles(
                    sideTitles: SideTitles(showTitles: false),
                  ),
                ),
                gridData: FlGridData(
                  show: true,
                  checkToShowHorizontalLine: (value) => value % 20 == 0,
                  getDrawingHorizontalLine: (value) => FlLine(
                    color: Theme.of(
                      context,
                    ).colorScheme.onSurface.withValues(alpha: 0.2),
                    strokeWidth: 1,
                    dashArray: [4, 4],
                  ),
                  drawVerticalLine: true,
                  getDrawingVerticalLine: (value) => FlLine(
                    color: Theme.of(
                      context,
                    ).colorScheme.onSurface.withValues(alpha: 0.2),
                    strokeWidth: 1,
                    dashArray: [4, 4],
                  ),
                ),
                borderData: FlBorderData(
                  show: true,
                  border: Border(
                    bottom: BorderSide(
                      color: Theme.of(
                        context,
                      ).colorScheme.onSurface.withValues(alpha: 0.5),
                    ),
                  ),
                ),
                barGroups: [
                  _buildBarGroup(
                    0,
                    fullDayPresent,
                    StatusColors.of(context).success.withValues(alpha: 0.7),
                  ),
                  _buildBarGroup(
                    1,
                    fullDayAbsent,
                    StatusColors.of(context).danger.withValues(alpha: 0.7),
                  ),
                  _buildBarGroup(
                    2,
                    firstHalfAbsent,
                    StatusColors.of(context).info.withValues(alpha: 0.7),
                  ),
                  _buildBarGroup(
                    3,
                    secondHalfAbsent,
                    StatusColors.of(context).warning.withValues(alpha: 0.7),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 24),
          Wrap(
            spacing: 15,
            runSpacing: 10,
            children: [
              _buildLegendItem(
                StatusColors.of(context).success,
                UiUtils.getTranslatedLabel(context, fullDayPresentKey),
              ),
              _buildLegendItem(
                StatusColors.of(context).danger,
                UiUtils.getTranslatedLabel(context, fullDayAbsentKey),
              ),
              _buildLegendItem(
                StatusColors.of(context).info,
                UiUtils.getTranslatedLabel(context, firstHalfAbsentKey),
              ),
              _buildLegendItem(
                StatusColors.of(context).warning,
                UiUtils.getTranslatedLabel(context, secondHalfAbsentKey),
              ),
            ],
          ),
        ],
      ),
    );
  }

  BarChartGroupData _buildBarGroup(int x, double y, Color color) {
    return BarChartGroupData(
      x: x,
      barRods: [
        BarChartRodData(
          toY: y,
          color: color,
          width: 40,
          borderRadius: BorderRadius.zero, // Make bars square as per screenshot
        ),
      ],
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
              height: 200,
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
      padding: EdgeInsets.only(
        top: 0,
        bottom: context.read<AuthCubit>().isParent()
            ? 0
            : UiUtils.getScrollViewBottomPadding(context) + 20,
      ),
      child: Column(
        children: [
          _buildFilters(),
          BlocBuilder<AttendanceReportCubit, AttendanceReportState>(
            builder: (context, state) {
              if (state is AttendanceReportFetchInProgress ||
                  state is AttendanceReportInitial) {
                return _buildContentShimmer();
              }
              if (state is AttendanceReportFetchFailure) {
                return Center(
                  child: ErrorContainer(
                    errorMessageCode: state.errorMessage,
                    onTapRetry: () {
                      _fetchReport();
                    },
                  ),
                );
              }
              if (state is AttendanceReportFetchSuccess) {
                return Column(
                  children: [
                    _buildCalendar(state.attendanceData),
                    _buildSummaryCards(state.attendanceData),
                    _buildChart(state.attendanceData),
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
