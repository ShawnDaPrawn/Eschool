import 'package:eschool_teacher/app/routes.dart';
import 'package:eschool_teacher/data/models/exam.dart';
import 'package:eschool_teacher/utils/labelKeys.dart';
import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:flutter/material.dart';

class HomeContainerExamItemContainer extends StatelessWidget {
  const HomeContainerExamItemContainer({required this.exam, super.key});
  final Exam exam;

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: () {
        //if examStartingDate is empty then there is no exam timetable
        if (exam.examStartingDate! == '') {
          UiUtils.showBottomToastOverlay(
            context: context,
            errorMessage: UiUtils.getTranslatedLabel(
              context,
              noExamTimeTableFoundKey,
            ),
            backgroundColor: Theme.of(context).colorScheme.error,
          );
          return;
        }
        Navigator.of(context).pushNamed(
          Routes.examTimeTable,
          arguments: {
            'examID': exam.examID,
            'examName': exam.examName.toString(),
            'studentId': null,
            'classId': exam.classId,
            'className': exam.className,
          },
        );
      },
      child: Container(
        height: double.maxFinite,
        width: 270,
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: Theme.of(context).colorScheme.surface,
          borderRadius: BorderRadius.circular(8),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Text(
              '${UiUtils.getTranslatedLabel(context, classKey)} : ${exam.className?.isEmpty ?? true ? '--' : exam.className}',
              textAlign: TextAlign.start,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: TextStyle(
                color: Theme.of(context).colorScheme.onSurface,
                fontSize: 14,
                fontWeight: FontWeight.w400,
              ),
            ),
            const SizedBox(
              height: 6,
            ),
            Text(
              exam.examName ?? '--',
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
              style: TextStyle(
                color: Theme.of(context).colorScheme.secondary,
                fontSize: 16,
                fontWeight: FontWeight.bold,
              ),
            ),
            const Spacer(),
            Text.rich(
              TextSpan(
                children: [
                  WidgetSpan(
                    alignment: PlaceholderAlignment.middle,
                    child: Icon(
                      Icons.calendar_month_outlined,
                      size: 18,
                      color: Theme.of(context).colorScheme.onSurface,
                    ),
                  ),
                  TextSpan(
                    text:
                        ' ${DateTime.tryParse(exam.examStartingDate ?? "") == null ? '--' : UiUtils.formatDate(DateTime.parse(exam.examStartingDate!))}',
                  ),
                ],
              ),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: TextStyle(
                color: Theme.of(context).colorScheme.onSurface,
                fontSize: 14,
                fontWeight: FontWeight.w400,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
