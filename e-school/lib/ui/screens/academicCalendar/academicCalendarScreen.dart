// ignore_for_file: public_member_api_docs, sort_constructors_first
import 'package:eschool/cubits/academicCalendarCubit.dart';
import 'package:eschool/cubits/academicCalendarPdfDownloadCubit.dart';
import 'package:eschool/cubits/appConfigurationCubit.dart';
import 'package:eschool/cubits/authCubit.dart';
import 'package:eschool/data/models/academicCalendar.dart';
import 'package:eschool/data/models/event.dart';
import 'package:eschool/data/models/exam.dart';
import 'package:eschool/data/models/holiday.dart';
import 'package:eschool/data/models/semesterBreak.dart';
import 'package:eschool/data/repositories/studentRepository.dart';
import 'package:eschool/data/repositories/systemInfoRepository.dart';
import 'package:eschool/ui/screens/academicCalendar/widgets/calendarOfflineExamContainer.dart';
import 'package:eschool/ui/screens/academicCalendar/widgets/downloadAcademicCalendarContainer.dart';
import 'package:eschool/ui/screens/academicCalendar/widgets/holidayContainer.dart';
import 'package:eschool/ui/screens/academicCalendar/widgets/semesterBreakContainer.dart';
import 'package:eschool/ui/styles/colors.dart';
import 'package:eschool/ui/widgets/changeCalendarMonthButton.dart';
import 'package:eschool/ui/widgets/customBackButton.dart';
import 'package:eschool/ui/widgets/customShimmerContainer.dart';
import 'package:eschool/ui/widgets/errorContainer.dart';
import 'package:eschool/ui/widgets/listItemForEvents.dart';
import 'package:eschool/ui/widgets/noDataContainer.dart';
import 'package:eschool/ui/widgets/screenTopBackgroundContainer.dart';
import 'package:eschool/ui/widgets/shimmerLoadingContainer.dart';
import 'package:eschool/utils/animationConfiguration.dart';
import 'package:eschool/utils/constants.dart';
import 'package:eschool/utils/extensions.dart';
import 'package:eschool/utils/labelKeys.dart';
import 'package:eschool/utils/uiUtils.dart';
import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:table_calendar/table_calendar.dart';

class AcademicCalendarScreen extends StatefulWidget {
  final bool hasBack;
  final int? childId;
  const AcademicCalendarScreen({
    required this.hasBack,
    super.key,
    this.childId,
  });

  static Color holidayColor = onPrimaryColor;
  static Color eventColor = primaryColor;
  static Color examColor = redColor;
  static Color semesterBreakColor = greyColor;

  static Route route(RouteSettings routeSettings) {
    final arguments = routeSettings.arguments! as Map<String, dynamic>;
    return CupertinoPageRoute(
      builder: (_) => BlocProvider<AcademicCalendarCubit>(
        create: (context) => AcademicCalendarCubit(
          SystemRepository(),
          StudentRepository(),
          appConfigurationCubit: context.read<AppConfigurationCubit>(),
        ),
        child: AcademicCalendarScreen(
          hasBack: arguments['hasBack'] ?? false,
          childId: arguments['childId'],
        ),
      ),
    );
  }

  @override
  State<AcademicCalendarScreen> createState() => _AcademicCalendarScreenState();
}

class _AcademicCalendarScreenState extends State<AcademicCalendarScreen>
    with SingleTickerProviderStateMixin {
  PageController? calendarPageController;
  int currentTabIndex = 0;

  //last and first day of calendar
  DateTime firstDay = DateTime.now();
  DateTime lastDay = DateTime.now();

  List<AcademicCalendar> monthItems = [];
  List<({DateTime date, Color highlightColor})> highlightedDates = [];
  DateTime focusedDay = DateTime.now();

  @override
  void initState() {
    Future.delayed(Duration.zero, () {
      context.read<AcademicCalendarCubit>().fetchData(childId: widget.childId);
    });
    super.initState();
  }

  void updateMonthWiseItems() {
    monthItems.clear();
    highlightedDates.clear();
    for (final item in context.read<AcademicCalendarCubit>().calendarItems()) {
      if (item.date != null) {
        if (item.date!.month == focusedDay.month &&
            item.date!.year == focusedDay.year) {
          monthItems.add(item);
          highlightedDates.add((
            date: item.date!,
            highlightColor: item.highlightColor,
          ));
          if (item.rangeEndDate != null) {
            highlightedDates.add((
              date: item.rangeEndDate!,
              highlightColor: item.highlightColor,
            ));
            for (
              DateTime date = item.date!.add(const Duration(days: 1));
              date.isBefore(item.rangeEndDate!);
              date = date.add(const Duration(days: 1))
            ) {
              highlightedDates.add((
                date: date,
                highlightColor: item.highlightColor,
              ));
            }
          }
        }
      }
    }
    monthItems.sort((first, second) => first.date!.compareTo(second.date!));
    setState(() {});
  }

  Widget _buildTabBar() {
    return Container(
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.secondary.withValues(alpha: 0.05),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Row(
        children: List.generate(4, (index) {
          return Flexible(
            child: InkWell(
              borderRadius: BorderRadius.circular(4),
              onTap: () {
                setState(() {
                  currentTabIndex = index;
                });
              },
              child: AnimatedContainer(
                duration: const Duration(milliseconds: 300),
                decoration: currentTabIndex == index
                    ? BoxDecoration(
                        color: Theme.of(context).scaffoldBackgroundColor,
                        borderRadius: BorderRadius.circular(4),
                        boxShadow: const [
                          BoxShadow(color: Colors.black12, blurRadius: 1),
                        ],
                      )
                    : null,
                margin: const EdgeInsets.all(5),
                padding: const EdgeInsets.symmetric(vertical: 8),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Text(
                      UiUtils.getTranslatedLabel(
                        context,
                        index == 0
                            ? allKey
                            : index == 1
                            ? holidaysKey
                            : index == 2
                            ? eventsKey
                            : examsKey,
                      ),
                      style: TextStyle(
                        color: currentTabIndex == index && index == 0
                            ? Theme.of(context).colorScheme.primary
                            : Theme.of(context).colorScheme.secondary,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                    if (index != 0) ...[
                      const SizedBox(width: 5),
                      Container(
                        width: 5,
                        height: 5,
                        decoration: BoxDecoration(
                          shape: BoxShape.circle,
                          color: index == 1
                              ? AcademicCalendarScreen.holidayColor
                              : index == 2
                              ? AcademicCalendarScreen.eventColor
                              : index == 3
                              ? AcademicCalendarScreen.examColor
                              : null,
                        ),
                      ),
                    ],
                  ],
                ),
              ),
            ),
          );
        }).toList(),
      ),
    );
  }

  Widget _buildAppBar() {
    return ScreenTopBackgroundContainer(
      heightPercentage: UiUtils.appBarSmallerHeightPercentage,
      child: LayoutBuilder(
        builder: (context, boxConstraints) {
          return Stack(
            clipBehavior: Clip.none,
            children: [
              context.read<AuthCubit>().isParent() || widget.hasBack
                  ? const CustomBackButton()
                  : const SizedBox(),
              Align(
                alignment: Alignment.topCenter,
                child: Text(
                  UiUtils.getTranslatedLabel(context, academicCalendarKey),
                  style: TextStyle(
                    color: Theme.of(context).scaffoldBackgroundColor,
                    fontSize: UiUtils.screenTitleFontSize,
                  ),
                ),
              ),
              Align(
                alignment: AlignmentDirectional.topEnd,
                child: Padding(
                  padding: EdgeInsetsDirectional.only(
                    end: UiUtils.screenContentHorizontalPadding,
                  ),
                  child: BlocProvider(
                    create: (context) =>
                        AcademicCalendarPdfDownloadCubit(SystemRepository()),
                    child: DownloadAcademicCalendarContainer(
                      childId: widget.childId,
                    ),
                  ),
                ),
              ),
            ],
          );
        },
      ),
    );
  }

  Widget _buildAcademicItemList(List<AcademicCalendar> academicCalendarItems) {
    return Column(
      children: List.generate(academicCalendarItems.length, (index) {
        final item = academicCalendarItems[index];
        return Animate(
          key: isApplicationItemAnimationOn ? UniqueKey() : null,
          effects: listItemAppearanceEffects(
            itemIndex: index,
            totalLoadedItems: academicCalendarItems.length,
          ),
          child: item.data is Holiday
              ? HolidayContainer(holiday: item.data as Holiday)
              : item.data is Event
              ? EventItemContainer(event: item.data as Event)
              : item.data is Exam
              ? Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: List.generate(
                    (item.data as Exam).timetable?.length ?? 0,
                    (index) => CalendarOfflineExamContainer(
                      exam: item.data as Exam,
                      subjectIndex: index,
                    ),
                  ),
                )
              : item.data is SemesterBreak
              ? SemesterBreakContainer(
                  semesterBreak: item.data as SemesterBreak,
                )
              : const SizedBox.shrink(),
        );
      }),
    );
  }

  Widget _buildCalendarContainer() {
    return Container(
      padding: const EdgeInsets.all(5),
      decoration: BoxDecoration(
        color: Theme.of(context).scaffoldBackgroundColor,
        boxShadow: [
          BoxShadow(
            color: Theme.of(
              context,
            ).colorScheme.secondary.withValues(alpha: 0.075),
            offset: const Offset(5, 5),
            blurRadius: 10,
          ),
        ],
        borderRadius: BorderRadius.circular(15),
      ),
      margin: const EdgeInsets.only(top: 20),
      child: TableCalendar(
        headerVisible: false,
        daysOfWeekHeight: 40,
        onPageChanged: (focused) {
          setState(() {
            focusedDay = focused;
          });
          updateMonthWiseItems();
        },
        onCalendarCreated: (controller) {
          calendarPageController = controller;
        },
        calendarBuilders: CalendarBuilders(
          rangeHighlightBuilder: (context, day, isWithinRange) {
            final Set<Color> highlightToBeApplied = highlightedDates
                .where((highlight) => highlight.date.isSameAs(day))
                .toList()
                .map<Color>((item) => item.highlightColor)
                .toSet();
            if (highlightToBeApplied.isNotEmpty) {
              return Align(
                alignment: Alignment.bottomCenter,
                child: Padding(
                  padding: const EdgeInsets.only(bottom: 8),
                  child: SizedBox(
                    height: 5,
                    child: ListView.separated(
                      scrollDirection: Axis.horizontal,
                      shrinkWrap: true,
                      physics: const NeverScrollableScrollPhysics(),
                      separatorBuilder: (context, index) =>
                          const SizedBox(width: 2),
                      itemCount: highlightToBeApplied.length,
                      itemBuilder: (context, index) => Container(
                        width: 5,
                        height: 5,
                        decoration: BoxDecoration(
                          color: highlightToBeApplied.elementAt(index),
                          shape: BoxShape.circle,
                        ),
                      ),
                    ),
                  ),
                ),
              );
            }
            return null;
          },
        ),
        availableGestures: AvailableGestures.none,
        calendarStyle: const CalendarStyle(isTodayHighlighted: false),
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
        firstDay: firstDay, //start education year
        lastDay: lastDay, //end education year
        focusedDay: focusedDay,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Stack(
        children: [
          BlocConsumer<AcademicCalendarCubit, AcademicCalendarState>(
            listener: (context, state) {
              if (state is AcademicCalendarFetchSuccess) {
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
                if (!UiUtils.isTodayInSessionYear(
                  context
                      .read<AppConfigurationCubit>()
                      .getAppConfiguration()
                      .sessionYear
                      .startDate,
                  context
                      .read<AppConfigurationCubit>()
                      .getAppConfiguration()
                      .sessionYear
                      .endDate,
                )) {
                  focusedDay = firstDay;
                }
                updateMonthWiseItems();
              }
            },
            builder: (context, state) {
              if (state is AcademicCalendarFetchSuccess) {
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
                  child: Column(
                    children: [
                      Container(
                        height: 50,
                        decoration: BoxDecoration(
                          borderRadius: BorderRadius.circular(10),
                          boxShadow: [
                            BoxShadow(
                              color: Theme.of(
                                context,
                              ).colorScheme.secondary.withValues(alpha: 0.075),
                              offset: const Offset(2.5, 2.5),
                              blurRadius: 5,
                            ),
                          ],
                          color: Theme.of(context).scaffoldBackgroundColor,
                        ),
                        width: MediaQuery.sizeOf(context).width * 0.85,
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
                                onTap: () {
                                  if (context
                                          .read<AcademicCalendarCubit>()
                                          .state
                                      is AcademicCalendarFetchInProgress) {
                                    return;
                                  }
                                  calendarPageController?.previousPage(
                                    duration: const Duration(milliseconds: 400),
                                    curve: Curves.easeInOut,
                                  );
                                },
                                isDisable: false,
                                isNextButton: false,
                              ),
                            ),
                            Align(
                              alignment: AlignmentDirectional.centerEnd,
                              child: ChangeCalendarMonthButton(
                                onTap: () {
                                  if (context
                                          .read<AcademicCalendarCubit>()
                                          .state
                                      is AcademicCalendarFetchInProgress) {
                                    return;
                                  }
                                  calendarPageController?.nextPage(
                                    duration: const Duration(milliseconds: 400),
                                    curve: Curves.easeInOut,
                                  );
                                },
                                isDisable: false,
                                isNextButton: true,
                              ),
                            ),
                          ],
                        ),
                      ),
                      Column(
                        children: [
                          _buildCalendarContainer(),
                          SizedBox(
                            height: MediaQuery.sizeOf(context).height * 0.025,
                          ),
                          if (monthItems.isNotEmpty) ...[
                            _buildTabBar(),
                            SizedBox(
                              height: MediaQuery.sizeOf(context).height * 0.025,
                            ),
                            IndexedStack(
                              index: currentTabIndex,
                              children: [
                                _buildAcademicItemList(monthItems),
                                monthItems.any((item) => item.data is Holiday)
                                    ? _buildAcademicItemList(
                                        monthItems
                                            .where(
                                              (item) => item.data is Holiday,
                                            )
                                            .toList(),
                                      )
                                    : const NoDataContainer(
                                        titleKey: noHolidaysThisMonthKey,
                                      ),
                                monthItems.any((item) => item.data is Event)
                                    ? _buildAcademicItemList(
                                        monthItems
                                            .where((item) => item.data is Event)
                                            .toList(),
                                      )
                                    : const NoDataContainer(
                                        titleKey: noEventsThisMonthKey,
                                      ),
                                monthItems.any((item) => item.data is Exam)
                                    ? _buildAcademicItemList(
                                        monthItems
                                            .where((item) => item.data is Exam)
                                            .toList(),
                                      )
                                    : const NoDataContainer(
                                        titleKey: noExamsThisMonthKey,
                                      ),
                              ],
                            ),
                          ],
                        ],
                      ),
                    ],
                  ),
                );
              } else if (state is AcademicCalendarFetchFailure) {
                return Padding(
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
                  child: Center(
                    child: ErrorContainer(
                      errorMessageCode: state.errorMessage,
                      onTapRetry: () {
                        context.read<AcademicCalendarCubit>().fetchData(
                          childId: widget.childId,
                        );
                      },
                    ),
                  ),
                );
              }
              return Padding(
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
                child: Column(
                  children: [
                    const SizedBox(height: 25),
                    ShimmerLoadingContainer(
                      child: CustomShimmerContainer(
                        height: MediaQuery.sizeOf(context).height * 0.425,
                      ),
                    ),
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
