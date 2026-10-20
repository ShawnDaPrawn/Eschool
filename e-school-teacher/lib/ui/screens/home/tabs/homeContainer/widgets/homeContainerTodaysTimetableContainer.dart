import 'package:eschool_teacher/data/models/timeTableSlot.dart';
import 'package:eschool_teacher/ui/widgets/customImageWidget.dart';
import 'package:eschool_teacher/utils/animationConfiguration.dart';
import 'package:eschool_teacher/utils/labelKeys.dart';
import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:url_launcher/url_launcher.dart';

class HomeContainerTodaysTimetableContainer extends StatefulWidget {
  const HomeContainerTodaysTimetableContainer({
    required this.timeTableSlots, super.key,
  });
  final List<TimeTableSlot> timeTableSlots;

  @override
  State<HomeContainerTodaysTimetableContainer> createState() =>
      _HomeContainerTodaysTimetableContainerState();
}

class _HomeContainerTodaysTimetableContainerState
    extends State<HomeContainerTodaysTimetableContainer>
    with TickerProviderStateMixin {
  int appearDisappearAnimationDurationMilliseconds = 600;

  final ValueNotifier<bool> _isExpanded = ValueNotifier(false);
  late AnimationController _controller;
  late Animation<double> _animation;

  @override
  void initState() {
    super.initState();

    _controller = AnimationController(
      duration:
          Duration(milliseconds: appearDisappearAnimationDurationMilliseconds),
      vsync: this,
    );
    _animation = CurvedAnimation(
      parent: _controller,
      curve: Curves.fastLinearToSlowEaseIn,
    );
  }

  @override
  void dispose() {
    _isExpanded.dispose();
    _controller.dispose();
    super.dispose();
  }

  void _toggleContainer() {
    if (_animation.status != AnimationStatus.completed) {
      _controller.forward();
      _isExpanded.value = true;
    } else {
      _controller.animateBack(
        0,
        duration: Duration(
          milliseconds: appearDisappearAnimationDurationMilliseconds,
        ),
        curve: Curves.fastLinearToSlowEaseIn,
      );
      _isExpanded.value = false;
    }
  }

  Widget _buildTimetableItem({
    required TimeTableSlot timetable,
    required bool isLastItem,
  }) {
    return Container(
      height: 130,
      margin: EdgeInsets.only(bottom: isLastItem ? 0 : 12),
      decoration: BoxDecoration(
        color: Theme.of(context).scaffoldBackgroundColor,
        borderRadius: BorderRadius.circular(8),
      ),
      width: double.maxFinite,
      clipBehavior: Clip.antiAlias,
      padding: const EdgeInsets.all(8),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Container(
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(4),
              color: UiUtils.getColorFromHexValue(timetable.subject.bgColor)
                  .withValues(alpha: 0.1),
            ),
            padding: const EdgeInsets.all(8),
            alignment: Alignment.topCenter,
            child: Container(
              width: 50,
              height: 50,
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(2),
                color: UiUtils.getColorFromHexValue(timetable.subject.bgColor),
              ),
              child: CustomImageWidget(
                imagePath: timetable.subject.image,
                boxFit: BoxFit.scaleDown,
              ),
            ),
          ),
          const SizedBox(
            width: 8,
          ),
          Expanded(
            child: Padding(
              padding: const EdgeInsets.all(8),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                mainAxisSize: MainAxisSize.min,
                children: [
                  Row(
                    children: [
                      Expanded(
                        child: Text(
                          '${UiUtils.getTranslatedLabel(context, classKey)} : ${timetable.classSectionDetails.classSectionName}',
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: TextStyle(
                            color: UiUtils.getColorScheme(context).secondary,
                            fontWeight: FontWeight.w600,
                            fontSize: 16,
                          ),
                        ),
                      ),
                      if (timetable.linkCustomUrl != null &&
                          timetable.linkName != null) ...[
                        const SizedBox(
                          width: 5,
                        ),
                        Flexible(
                          child: Align(
                            alignment: AlignmentDirectional.centerEnd,
                            child: GestureDetector(
                              onTap: () {
                                try {
                                  launchUrl(
                                    Uri.parse(timetable.linkCustomUrl!),
                                    mode: LaunchMode.externalApplication,
                                  );
                                } catch (_) {}
                              },
                              child: Text(
                                timetable.linkName!,
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                                textAlign: TextAlign.end,
                                style: TextStyle(
                                  color: Theme.of(context).colorScheme.primary,
                                  fontWeight: FontWeight.w600,
                                  fontSize: 12,
                                ),
                              ),
                            ),
                          ),
                        ),
                      ],
                    ],
                  ),
                  const SizedBox(
                    height: 8,
                  ),
                  Text(
                    '${UiUtils.getTranslatedLabel(context, subjectKey)} : ${timetable.subject.name}',
                    textAlign: TextAlign.start,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(
                      height: 1,
                      fontWeight: FontWeight.w600,
                      color: Theme.of(context)
                          .colorScheme
                          .onSurface
                          .withValues(alpha: 0.8),
                      fontSize: 14,
                    ),
                  ),
                  const Spacer(),
                  Text.rich(
                    TextSpan(
                      children: [
                        WidgetSpan(
                          alignment: PlaceholderAlignment.middle,
                          child: Icon(
                            Icons.access_time,
                            size: 16,
                            color: Theme.of(context)
                                .colorScheme
                                .onSurface
                                .withValues(alpha: 0.8),
                          ),
                        ),
                        TextSpan(
                          text:
                              ' ${UiUtils.formatTime(timetable.startTime)} - ${UiUtils.formatTime(timetable.endTime)}',
                        ),
                      ],
                    ),
                    textAlign: TextAlign.start,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(
                      height: 1,
                      color: Theme.of(context)
                          .colorScheme
                          .onSurface
                          .withValues(alpha: 0.8),
                      fontSize: 14,
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            ...List.generate(
              widget.timeTableSlots.length > 3
                  ? 3
                  : widget.timeTableSlots.length,
              (index) {
                return _buildTimetableItem(
                  timetable: widget.timeTableSlots[index],
                  isLastItem:
                      index == 2 || index == widget.timeTableSlots.length - 1,
                );
              },
            ),
            if (widget.timeTableSlots.length > 3)
              SizeTransition(
                sizeFactor: _animation,
                child: Column(
                  children: [
                    const SizedBox(
                      height: 20,
                    ),
                    ...List.generate(
                      widget.timeTableSlots.length > 3
                          ? widget.timeTableSlots.length - 3
                          : 0,
                      (index) {
                        return _buildTimetableItem(
                          timetable: widget.timeTableSlots[index + 3],
                          isLastItem:
                              index + 3 == widget.timeTableSlots.length - 1,
                        );
                      },
                    ),
                  ],
                ),
              ),
            const SizedBox(
              height: 10,
            ),
          ],
        ),
        if (widget.timeTableSlots.length > 3)
          ValueListenableBuilder(
            valueListenable: _isExpanded,
            builder: (context, isExpanded, _) {
              return Animate(
                key: ValueKey(isExpanded),
                effects: customItemFadeAppearanceEffects(),
                child: Center(
                  child: TextButton(
                    onPressed: () {
                      _toggleContainer();
                    },
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Text(
                          UiUtils.getTranslatedLabel(
                            context,
                            isExpanded ? viewLessKey : viewMoreKey,
                          ),
                          textAlign: TextAlign.center,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: TextStyle(
                            color: UiUtils.getColorScheme(context).secondary,
                            fontWeight: FontWeight.w500,
                            fontSize: 14,
                          ),
                        ),
                        const SizedBox(
                          width: 4,
                        ),
                        Icon(
                          isExpanded
                              ? Icons.arrow_drop_up
                              : Icons.arrow_drop_down,
                          color: UiUtils.getColorScheme(context).secondary,
                          size: 20,
                        ),
                      ],
                    ),
                  ),
                ),
              );
            },
          ),
      ],
    );
  }
}
