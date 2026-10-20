import 'package:eschool_teacher/app/routes.dart';
import 'package:eschool_teacher/data/models/classSectionDetails.dart';
import 'package:eschool_teacher/utils/animationConfiguration.dart';
import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';

class SubjectTeacherClassContainer extends StatelessWidget {
  const SubjectTeacherClassContainer({
    required this.classDetails, required this.index, super.key,
  });
  final ClassSectionDetails classDetails;
  final int index;

  @override
  Widget build(BuildContext context) {
    final Color color = UiUtils.getClassColor(index);
    return Animate(
      effects: customItemZoomAppearanceEffects(),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          borderRadius: BorderRadius.circular(4),
          onTap: () {
            Navigator.of(context).pushNamed(
              Routes.classScreen,
              arguments: {
                'isClassTeacher': false,
                'classSection': classDetails,
              },
            );
          },
          child: Container(
            decoration: BoxDecoration(
              color: Theme.of(context).scaffoldBackgroundColor,
              borderRadius: BorderRadius.circular(4),
            ),
            constraints: const BoxConstraints(minWidth: 170, maxWidth: 250),
            padding: const EdgeInsets.all(12),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisSize: MainAxisSize.min,
              children: [
                Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Container(
                      height: 34,
                      width: 6,
                      decoration: BoxDecoration(
                        color: color,
                        borderRadius: BorderRadius.circular(8),
                      ),
                    ),
                    const SizedBox(
                      width: 6,
                    ),
                    Flexible(
                      child: Text(
                        classDetails.getClassSectionNameWithSemester(
                          context: context,
                        ),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        textAlign: TextAlign.start,
                        style: TextStyle(
                          color: Theme.of(context).colorScheme.secondary,
                          fontWeight: FontWeight.w600,
                          fontSize: 16,
                          height: 1,
                        ),
                      ),
                    ),
                  ],
                ),
                const Spacer(),
                Text(
                  "${classDetails.classDetails.medium.name} ${UiUtils.getTranslatedLabel(context, "medium")}${classDetails.classDetails.shift != null ? '\n${classDetails.classDetails.shift!.title} ${UiUtils.getTranslatedLabel(context, "shift")}' : ''}",
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  textAlign: TextAlign.start,
                  style: TextStyle(
                    color: Theme.of(context)
                        .colorScheme
                        .secondary
                        .withValues(alpha: 0.8),
                    fontSize: 14,
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
