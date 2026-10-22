import 'package:eschool_teacher/cubits/timeTableCubit.dart';
import 'package:eschool_teacher/cubits/updateTimetableLinkCubit.dart';
import 'package:eschool_teacher/data/models/timeTableSlot.dart';
import 'package:eschool_teacher/data/repositories/teacherRepository.dart';
import 'package:eschool_teacher/ui/screens/class/widgets/subjectImageContainer.dart';
import 'package:eschool_teacher/ui/screens/home/widgets/addUpdateTimetableLinkBottomsheetContainer.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_teacher/ui/widgets/noDataContainer.dart';
import 'package:eschool_teacher/utils/labelKeys.dart';
import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

class TimeTableContainer extends StatefulWidget {
  const TimeTableContainer({super.key});

  @override
  State<TimeTableContainer> createState() => _TimeTableContainerState();
}

class _TimeTableContainerState extends State<TimeTableContainer> {
  late int _currentSelectedDayIndex = DateTime.now().weekday - 1;

  @override
  void initState() {
    super.initState();
    Future.delayed(Duration.zero, () {
      if (context.mounted) {
        context.read<TimeTableCubit>().fetchTimeTable();
      }
    });
  }

  Widget _buildAppBar() {
    return ScreenTopBackgroundContainer(
      heightPercentage: UiUtils.appBarSmallerHeightPercentage,
      padding: EdgeInsets.zero,
      child: Stack(
        clipBehavior: Clip.none,
        children: [
          Align(
            child: Text(
              UiUtils.getTranslatedLabel(context, scheduleKey),
              style: TextStyle(
                color: Theme.of(context).colorScheme.onInverseSurface,
                fontSize: UiUtils.screenTitleFontSize,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildDayContainer(int index) {
    return InkWell(
      onTap: () {
        setState(() {
          _currentSelectedDayIndex = index;
        });
      },
      borderRadius: BorderRadius.circular(5),
      child: Container(
        alignment: Alignment.center,
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(5),
          color: index == _currentSelectedDayIndex
              ? Theme.of(context).colorScheme.primary
              : Colors.transparent,
        ),
        padding: const EdgeInsets.all(7.5),
        child: Text(
          UiUtils.weekDays[index],
          style: TextStyle(
            fontSize: 13,
            fontWeight: FontWeight.w600,
            color: index == _currentSelectedDayIndex
                ? Theme.of(context).colorScheme.onPrimary
                : Theme.of(context).colorScheme.primary,
          ),
        ),
      ),
    );
  }

  Widget _buildDays() {
    final List<Widget> children = [];

    for (var i = 0; i < UiUtils.weekDays.length; i++) {
      children.add(_buildDayContainer(i));
    }

    return SizedBox(
      width: MediaQuery.sizeOf(context).width * 0.85,
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: children,
      ),
    );
  }

  Future<void> _openAddUpdateTimetableBottomsheetContainer(
    TimeTableSlot timeTableSlot,
  ) async {
    final didChange = await showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      builder: (context) {
        return BlocProvider(
          create: (context) => UpdateTimetableLinkCubit(TeacherRepository()),
          child: AddUpdateTimetableLinkBottomsheetContaienr(
            timetableSlot: timeTableSlot,
          ),
        );
      },
    );
    if (didChange == true) {
      setState(() {});
    }
  }

  Widget _buildTimeTableSlotDetainsContainer(TimeTableSlot timeTableSlot) {
    return Container(
      margin: const EdgeInsets.only(bottom: 20),
      decoration: BoxDecoration(
        boxShadow: [
          BoxShadow(
            color: Theme.of(context).colorScheme.shadow.withValues(alpha: 0.05),
            offset: const Offset(2.5, 2.5),
            blurRadius: 10,
          ),
        ],
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.circular(10),
      ),
      width: MediaQuery.sizeOf(context).width * 0.85,
      padding: const EdgeInsets.symmetric(horizontal: 12.5, vertical: 10),
      child: LayoutBuilder(
        builder: (context, boxConstraints) {
          return Row(
            children: [
              SubjectImageContainer(
                showShadow: false,
                height: 60,
                width: boxConstraints.maxWidth * 0.2,
                radius: 7.5,
                subject: timeTableSlot.subject,
              ),
              SizedBox(width: boxConstraints.maxWidth * 0.05),
              SizedBox(
                width: boxConstraints.maxWidth * 0.75,
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Text(
                          '${UiUtils.formatTime(timeTableSlot.startTime)} - ${UiUtils.formatTime(timeTableSlot.endTime)}',
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: TextStyle(
                            color: Theme.of(context).colorScheme.onSurface,
                            fontWeight: FontWeight.w600,
                            fontSize: 14,
                          ),
                        ),
                        const SizedBox(width: 5),
                        Expanded(
                          child: Text(
                            timeTableSlot.classSectionDetails
                                .getClassSectionNameWithMedium(),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: TextStyle(
                              color: Theme.of(
                                context,
                              ).colorScheme.onSurfaceVariant,
                              fontWeight: FontWeight.w400,
                              fontSize: 12,
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 2.5),
                    Text(
                      timeTableSlot.subject.subjectNameWithTypeAndSemester,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: TextStyle(
                        color: Theme.of(context).colorScheme.onSurfaceVariant,
                        fontWeight: FontWeight.w400,
                        fontSize: 12,
                      ),
                    ),
                    if (timeTableSlot.hasCustomLink) ...[
                      const SizedBox(height: 2.5),
                      Row(
                        children: [
                          Flexible(
                            child: GestureDetector(
                              onTap: () {
                                UiUtils.launchWebUrl(
                                  timeTableSlot.linkCustomUrl ?? '',
                                );
                              },
                              child: Text(
                                timeTableSlot.linkName!,
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                                style: TextStyle(
                                  color: Theme.of(context).colorScheme.primary,
                                  fontWeight: FontWeight.w600,
                                  fontSize: 12,
                                ),
                              ),
                            ),
                          ),
                          const SizedBox(width: 4),
                          GestureDetector(
                            onTap: () {
                              _openAddUpdateTimetableBottomsheetContainer(
                                timeTableSlot,
                              );
                            },
                            child: Icon(
                              Icons.edit_outlined,
                              color: Theme.of(context).colorScheme.primary,
                              size: 18,
                            ),
                          ),
                        ],
                      ),
                    ] else ...[
                      GestureDetector(
                        onTap: () {
                          _openAddUpdateTimetableBottomsheetContainer(
                            timeTableSlot,
                          );
                        },
                        child: Icon(
                          Icons.add_link,
                          color: Theme.of(context).colorScheme.primary,
                          size: 18,
                        ),
                      ),
                    ],
                  ],
                ),
              ),
            ],
          );
        },
      ),
    );
  }

  List<TimeTableSlot> _buildTimeTableSlots(List<TimeTableSlot> timeTableSlot) {
    final dayWiseTimeTableSlots = timeTableSlot
        .where((element) => element.day == _currentSelectedDayIndex + 1)
        .toList();
    return dayWiseTimeTableSlots;
  }

  Widget _buildTimeTableShimmerLoadingContainer() {
    return Container(
      margin: const EdgeInsets.only(bottom: 20),
      width: MediaQuery.sizeOf(context).width,
      padding: EdgeInsets.symmetric(
        horizontal:
            UiUtils.screenContentHorizontalPaddingPercentage *
            MediaQuery.sizeOf(context).width,
      ),
      child: ShimmerLoadingContainer(
        child: LayoutBuilder(
          builder: (context, boxConstraints) {
            return Row(
              children: [
                CustomShimmerContainer(
                  height: 60,
                  width: boxConstraints.maxWidth * 0.25,
                ),
                SizedBox(width: boxConstraints.maxWidth * 0.05),
                Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    CustomShimmerContainer(
                      height: 9,
                      width: boxConstraints.maxWidth * 0.6,
                    ),
                    const SizedBox(height: 10),
                    CustomShimmerContainer(
                      height: 8,
                      width: boxConstraints.maxWidth * 0.5,
                    ),
                  ],
                ),
              ],
            );
          },
        ),
      ),
    );
  }

  Widget _buildTimeTable() {
    return BlocBuilder<TimeTableCubit, TimeTableState>(
      builder: (context, state) {
        if (state is TimeTableFetchSuccess) {
          final timetableSlots = _buildTimeTableSlots(state.timetableSlots);
          return timetableSlots.isEmpty
              ? NoDataContainer(
                  key: isApplicationItemAnimationOn ? UniqueKey() : null,
                  titleKey: noLecturesKey,
                )
              : SizedBox(
                  width: MediaQuery.sizeOf(context).width,
                  child: Column(
                    children: List.generate(
                      timetableSlots.length,
                      (index) => Animate(
                        key: isApplicationItemAnimationOn
                            ? ValueKey(
                                timetableSlots[index].id +
                                    _currentSelectedDayIndex.toString(),
                              )
                            : null,
                        effects: listItemAppearanceEffects(
                          itemIndex: index,
                          totalLoadedItems: timetableSlots.length,
                        ),
                        child: _buildTimeTableSlotDetainsContainer(
                          timetableSlots[index],
                        ),
                      ),
                    ),
                  ),
                );
        }

        if (state is TimeTableFetchFailure) {
          return ErrorContainer(
            key: isApplicationItemAnimationOn ? UniqueKey() : null,
            errorMessageCode: state.errorMessage,
            onTapRetry: () {
              context.read<TimeTableCubit>().fetchTimeTable();
            },
          );
        }

        return Column(
          children: List.generate(
            UiUtils.defaultShimmerLoadingContentCount,
            (index) => index,
          ).map((e) => _buildTimeTableShimmerLoadingContainer()).toList(),
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    return Stack(
      children: [
        Align(
          alignment: Alignment.topCenter,
          child: SingleChildScrollView(
            padding: EdgeInsets.only(
              bottom: UiUtils.getScrollViewBottomPadding(context),
              top: UiUtils.getScrollViewTopPadding(
                context: context,
                appBarHeightPercentage: UiUtils.appBarSmallerHeightPercentage,
              ),
            ),
            child: Column(
              children: [
                _buildDays(),
                SizedBox(height: MediaQuery.sizeOf(context).height * 0.025),
                _buildTimeTable(),
              ],
            ),
          ),
        ),
        Align(alignment: Alignment.topCenter, child: _buildAppBar()),
      ],
    );
  }
}
