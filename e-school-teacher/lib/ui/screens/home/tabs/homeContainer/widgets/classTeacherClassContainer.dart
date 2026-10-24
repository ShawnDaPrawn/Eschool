import 'package:eschool_teacher/app/routeArguments.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_teacher/app/routes.dart';
import 'package:eschool_teacher/data/models/classSectionDetails.dart';
import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';

class ClassTeacherClassContainer extends StatelessWidget {
  const ClassTeacherClassContainer({
    required this.classDetails,
    required this.index,
    super.key,
  });
  final ClassSectionDetails classDetails;
  final int index;

  @override
  Widget build(BuildContext context) {
    final bool doesClassIncludeStream =
        classDetails.classDetails.stream != null;
    final Color color = UiUtils.getClassColor(context, index);
    return Animate(
      effects: customItemZoomAppearanceEffects(),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          borderRadius: BorderRadius.circular(4),
          onTap: () {
            Navigator.of(context).pushNamed(
              Routes.classScreen,
              arguments: ClassScreenArguments(
                isClassTeacher: true,
                classSection: classDetails,
              ),
            );
          },
          child: Container(
            decoration: BoxDecoration(
              color: Theme.of(context).colorScheme.surfaceContainerLowest,
              borderRadius: BorderRadius.circular(4),
            ),
            padding: const EdgeInsets.all(12),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Expanded(
                  child: Align(
                    alignment: AlignmentDirectional.topStart,
                    child: Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 12,
                        vertical: 12,
                      ),
                      decoration: BoxDecoration(
                        color: color,
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Text(
                        () {
                          var value = classDetails.getClassSectionNameWithTerm(
                            context: context,
                          );
                          //removing stream name from class to not show multiple times
                          if (doesClassIncludeStream &&
                              classDetails.classDetails.stream?.name != null) {
                            value = value.replaceFirst(
                              classDetails.classDetails.stream?.name ?? '',
                              '',
                            );
                          }
                          return value;
                        }(),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        textAlign: TextAlign.start,
                        style: TextStyle(
                          color: Theme.of(context).colorScheme
                              .readableOn(color),
                          fontWeight: FontWeight.w600,
                          fontSize: 16,
                        ),
                      ),
                    ),
                  ),
                ),

                Text(
                  doesClassIncludeStream
                      ? (classDetails.classDetails.stream?.name ?? '')
                      : "${classDetails.classDetails.medium.name} ${UiUtils.getTranslatedLabel(context, "medium")}${classDetails.classDetails.shift != null ? '\n${classDetails.classDetails.shift!.title} ${UiUtils.getTranslatedLabel(context, "shift")}' : ''}",
                  maxLines: doesClassIncludeStream ? 1 : 2,
                  overflow: TextOverflow.ellipsis,
                  textAlign: TextAlign.start,
                  style: TextStyle(
                    fontWeight: FontWeight.bold,
                    color: Theme.of(context).colorScheme.onSurface,
                    fontSize: 16,
                  ),
                ),
                if (doesClassIncludeStream) ...[
                  const SizedBox(height: 4),
                  Text(
                    "${classDetails.classDetails.medium.name} ${UiUtils.getTranslatedLabel(context, "medium")}${classDetails.classDetails.shift != null ? '\n${classDetails.classDetails.shift!.title} ${UiUtils.getTranslatedLabel(context, "shift")}' : ''}",
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    textAlign: TextAlign.start,
                    style: TextStyle(
                      color: Theme.of(context).colorScheme.onSurface
                          .withValues(alpha: 0.8),
                      fontSize: 14,
                    ),
                  ),
                ],
              ],
            ),
          ),
        ),
      ),
    );
  }
}
