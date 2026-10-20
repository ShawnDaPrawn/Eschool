import 'package:eschool/data/models/exam.dart';
import 'package:eschool/ui/widgets/customImageWidget.dart';
import 'package:eschool/utils/labelKeys.dart';
import 'package:eschool/utils/uiUtils.dart';
import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';

class CalendarOfflineExamContainer extends StatelessWidget {
  const CalendarOfflineExamContainer({
    required this.exam, required this.subjectIndex, super.key,
  });
  final Exam exam;
  final int subjectIndex;

  @override
  Widget build(BuildContext context) {
    final hasStartingAndEndingTime =
        (exam.timetable![subjectIndex].startingTime?.isNotEmpty ?? false) &&
        (exam.timetable![subjectIndex].endingTime?.isNotEmpty ?? false);
    return Container(
      height: 130,
      decoration: BoxDecoration(
        color: UiUtils.getColorFromHexValue(
          exam.timetable![subjectIndex].subject?.bgColor ?? '000000',
        ).withValues(alpha: 0.15),
        borderRadius: BorderRadius.circular(10),
      ),
      margin: const EdgeInsets.only(bottom: 15),
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
                    exam
                            .timetable![subjectIndex]
                            .subject
                            ?.subjectNameWithType ??
                        '',
                    textAlign: TextAlign.start,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(
                      color: UiUtils.getColorFromHexValue(
                        exam.timetable![subjectIndex].subject?.bgColor ??
                            '000000',
                      ),
                      fontWeight: FontWeight.w600,
                      fontSize: 16,
                    ),
                  ),
                  const SizedBox(height: 5),
                  Text(
                    exam.examName ?? '',
                    textAlign: TextAlign.start,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(
                      color: UiUtils.getColorScheme(context).secondary,
                      fontSize: 16,
                    ),
                  ),
                  const SizedBox(height: 5),
                  Text(
                    '${exam.timetable![subjectIndex].totalMarks} ${UiUtils.getTranslatedLabel(context, marksKey)}',
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
                            padding: const EdgeInsetsDirectional.only(end: 2),
                            child: Icon(
                              Icons.calendar_month_outlined,
                              color: Theme.of(context).colorScheme.secondary,
                              size: 12,
                            ),
                          ),
                        ),
                        TextSpan(
                          text: UiUtils.formatDate(
                            DateTime.parse(exam.timetable![subjectIndex].date!),
                          ),
                        ),
                        const WidgetSpan(
                          alignment: PlaceholderAlignment.middle,
                          child: SizedBox(width: 15),
                        ),
                        if (hasStartingAndEndingTime) ...[
                          WidgetSpan(
                            alignment: PlaceholderAlignment.middle,
                            child: Padding(
                              padding: const EdgeInsetsDirectional.only(end: 2),
                              child: Icon(
                                CupertinoIcons.clock,
                                color: Theme.of(context).colorScheme.secondary,
                                size: 12,
                              ),
                            ),
                          ),
                          TextSpan(
                            text:
                                '${UiUtils.formatTime(exam.timetable![subjectIndex].startingTime!)} ${UiUtils.getTranslatedLabel(context, toKey)} ${UiUtils.formatTime(exam.timetable![subjectIndex].endingTime!)}',
                          ),
                        ],
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
                  exam.timetable![subjectIndex].subject?.bgColor ?? '000000',
                ),
              ),
              child: CustomImageWidget(
                imagePath: exam.timetable![subjectIndex].subject?.image ?? '',
                boxFit: BoxFit.scaleDown,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
