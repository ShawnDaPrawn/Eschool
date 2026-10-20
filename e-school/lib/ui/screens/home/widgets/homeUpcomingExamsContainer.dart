import 'package:eschool/data/models/exam.dart';
import 'package:eschool/ui/widgets/customImageWidget.dart';
import 'package:eschool/utils/animationConfiguration.dart';
import 'package:eschool/utils/labelKeys.dart';
import 'package:eschool/utils/uiUtils.dart';
import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';

class HomeUpcomingExamsContainer extends StatelessWidget {
  const HomeUpcomingExamsContainer({required this.exam, super.key});
  final Exam exam;

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: List.generate(
        exam.timetable?.length ?? 0,
        (index) => Animate(
          effects: customItemFadeAppearanceEffects(),
          child: Container(
            width: MediaQuery.sizeOf(context).width * 0.85,
            decoration: BoxDecoration(
              color: UiUtils.getColorFromHexValue(
                exam.timetable![index].subject?.bgColor ?? '000000',
              ).withValues(alpha: 0.15),
              borderRadius: BorderRadius.circular(10),
            ),
            margin: const EdgeInsetsDirectional.only(end: 20),
            clipBehavior: Clip.antiAlias,
            child: Padding(
              padding: const EdgeInsets.all(15),
              child: Row(
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Text(
                          exam.examName ?? '',
                          textAlign: TextAlign.start,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: TextStyle(
                            color: UiUtils.getColorFromHexValue(
                              exam.timetable![index].subject?.bgColor ??
                                  '000000',
                            ),
                            fontWeight: FontWeight.w600,
                            fontSize: 16,
                          ),
                        ),
                        const SizedBox(height: 5),
                        Text(
                          exam.timetable![index].subject?.name ?? '',
                          textAlign: TextAlign.start,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: TextStyle(
                            color: UiUtils.getColorScheme(context).secondary,
                            fontWeight: FontWeight.w600,
                            fontSize: 16,
                          ),
                        ),
                        const SizedBox(height: 5),
                        Text(
                          '${exam.timetable![index].totalMarks} ${UiUtils.getTranslatedLabel(context, marksKey)}',
                          textAlign: TextAlign.start,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: TextStyle(
                            color: Theme.of(context).colorScheme.secondary,
                            fontSize: 12,
                          ),
                        ),
                        const SizedBox(height: 10),
                        Text.rich(
                          TextSpan(
                            children: [
                              WidgetSpan(
                                alignment: PlaceholderAlignment.middle,
                                child: Padding(
                                  padding: const EdgeInsetsDirectional.only(
                                    end: 2,
                                  ),
                                  child: Icon(
                                    Icons.calendar_month_outlined,
                                    color: Theme.of(
                                      context,
                                    ).colorScheme.secondary,
                                    size: 12,
                                  ),
                                ),
                              ),
                              TextSpan(
                                text: UiUtils.formatDate(
                                  DateTime.parse(exam.timetable![index].date!),
                                ),
                              ),
                              const WidgetSpan(
                                alignment: PlaceholderAlignment.middle,
                                child: SizedBox(width: 15),
                              ),
                              WidgetSpan(
                                alignment: PlaceholderAlignment.middle,
                                child: Padding(
                                  padding: const EdgeInsetsDirectional.only(
                                    end: 2,
                                  ),
                                  child: Icon(
                                    CupertinoIcons.clock,
                                    color: Theme.of(
                                      context,
                                    ).colorScheme.secondary,
                                    size: 12,
                                  ),
                                ),
                              ),
                              TextSpan(
                                text:
                                    '${UiUtils.formatTime(exam.timetable![index].startingTime!)} ${UiUtils.getTranslatedLabel(context, toKey)} ${UiUtils.formatTime(exam.timetable![index].endingTime!)}',
                              ),
                            ],
                            style: TextStyle(
                              height: 1,
                              color: Theme.of(context).colorScheme.secondary,
                              fontWeight: FontWeight.w400,
                              fontSize: 10,
                            ),
                          ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 10),
                  Container(
                    width: 70,
                    height: 70,
                    decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      color: UiUtils.getColorFromHexValue(
                        exam.timetable![index].subject?.bgColor ?? '000000',
                      ),
                    ),
                    child: CustomImageWidget(
                      imagePath: exam.timetable![index].subject?.image ?? '',
                      boxFit: BoxFit.scaleDown,
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
