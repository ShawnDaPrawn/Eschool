import 'package:eschool_teacher/data/models/semesterBreak.dart';
import 'package:eschool_teacher/utils/labelKeys.dart';
import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:flutter/material.dart';

class SemesterBreakContainer extends StatelessWidget {
  final SemesterBreak semesterBreak;
  const SemesterBreakContainer({super.key, required this.semesterBreak});

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 15),
      padding: const EdgeInsets.symmetric(horizontal: 15, vertical: 10),
      width: MediaQuery.sizeOf(context).width * 0.85,
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.circular(10),
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            UiUtils.getTranslatedLabel(context, semesterBreakKey),
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: TextStyle(
              color: Theme.of(context).colorScheme.secondary,
              fontWeight: FontWeight.w600,
              fontSize: 14,
            ),
          ),
          const SizedBox(height: 5),
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
                  text:
                      "${UiUtils.formatDate(semesterBreak.startDate)} ${UiUtils.getTranslatedLabel(context, toKey)} ${UiUtils.formatDate(semesterBreak.endDate)}",
                ),
              ],
            ),
            style: TextStyle(
              color: Theme.of(context).colorScheme.onSurface,
              fontSize: 12,
            ),
            textAlign: TextAlign.start,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
          ),
        ],
      ),
    );
  }
}
