import 'package:eschool/data/models/timeTableSlot.dart';
import 'package:eschool/ui/widgets/customImageWidget.dart';
import 'package:eschool/utils/animationConfiguration.dart';
import 'package:eschool/utils/labelKeys.dart';
import 'package:eschool/utils/uiUtils.dart';
import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:url_launcher/url_launcher.dart';

class HomeTimetableContainer extends StatefulWidget {
  const HomeTimetableContainer({required this.timeTableSlots, super.key});
  final List<TimeTableSlot> timeTableSlots;

  @override
  State<HomeTimetableContainer> createState() => _HomeTimetableContainerState();
}

class _HomeTimetableContainerState extends State<HomeTimetableContainer>
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

  Container _buildTimetableItem({
    required TimeTableSlot timetable,
    required bool isLastItem,
  }) {
    return Container(
      margin: EdgeInsets.only(bottom: isLastItem ? 0 : 20),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.circular(10),
      ),
      width: double.maxFinite,
      clipBehavior: Clip.antiAlias,
      child: Padding(
        padding: const EdgeInsets.all(10),
        child: Row(
          children: [
            Container(
              width: 50,
              height: 50,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: UiUtils.getColorFromHexValue(timetable.subject.bgColor),
              ),
              child: CustomImageWidget(
                imagePath: timetable.subject.image,
                boxFit: BoxFit.scaleDown,
              ),
            ),
            const SizedBox(
              width: 10,
            ),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                mainAxisSize: MainAxisSize.min,
                children: [
                  Row(
                    children: [
                      Expanded(
                        child: Text(
                          timetable.subject.name,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: TextStyle(
                            color: UiUtils.getColorScheme(context).secondary,
                            fontWeight: FontWeight.w600,
                            fontSize: 14,
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
                    height: 5,
                  ),
                  Text(
                    '${UiUtils.formatTime(timetable.startTime)} ${UiUtils.getTranslatedLabel(context, toKey)} ${UiUtils.formatTime(timetable.endTime)} | ${timetable.teacherFirstName} ${timetable.teacherLastName}',
                    textAlign: TextAlign.start,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(
                      color: Theme.of(context)
                          .colorScheme
                          .onSurface
                          .withValues(alpha: 0.75),
                      fontSize: 12,
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
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
                    child: Text(
                      UiUtils.getTranslatedLabel(
                        context,
                        isExpanded ? viewLessKey : viewMoreKey,
                      ),
                      textAlign: TextAlign.center,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: TextStyle(
                        color: UiUtils.getColorScheme(context).primary,
                        fontWeight: FontWeight.w600,
                        fontSize: 16,
                      ),
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
