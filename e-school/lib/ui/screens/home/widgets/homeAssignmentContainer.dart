import 'package:eschool/app/routes.dart';
import 'package:eschool/cubits/assignmentsCubit.dart';
import 'package:eschool/cubits/studentDashboardCubit.dart';
import 'package:eschool/data/models/assignment.dart';
import 'package:eschool/ui/widgets/customImageWidget.dart';
import 'package:eschool/utils/animationConfiguration.dart';
import 'package:eschool/utils/labelKeys.dart';
import 'package:eschool/utils/uiUtils.dart';
import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

class HomeAssignmentContainer extends StatelessWidget {
  const HomeAssignmentContainer({
    required this.assignment,
    required this.parentContext,
    super.key,
  });
  final Assignment assignment;
  //need this context to access the assignments cubit
  final BuildContext parentContext;

  @override
  Widget build(BuildContext context) {
    return Animate(
      effects: customItemFadeAppearanceEffects(),
      child: Container(
        decoration: BoxDecoration(
          color: Theme.of(context).scaffoldBackgroundColor,
          borderRadius: BorderRadius.circular(10),
          boxShadow: const [BoxShadow(color: Colors.black26, blurRadius: 1)],
        ),
        margin: const EdgeInsets.only(bottom: 20),
        clipBehavior: Clip.antiAlias,
        child: Material(
          color: Colors.transparent,
          child: InkWell(
            borderRadius: BorderRadius.circular(10),
            onTap: () {
              Navigator.of(context)
                  .pushNamed<Assignment>(
                    Routes.assignment,
                    arguments: assignment,
                  )
                  .then((submittedAssignment) {
                    if (submittedAssignment != null) {
                      //change submitted assignment status on dashboard
                      context.read<StudentDashboardCubit>().updateAssignments(
                        submittedAssignment,
                      );

                      //update the same assignment if existing in the list in assignments cubit
                      parentContext.read<AssignmentsCubit>().updateAssignments(
                        submittedAssignment,
                      );
                    }
                  });
            },
            child: Padding(
              padding: const EdgeInsets.all(10),
              child: Row(
                children: [
                  Container(
                    width: 50,
                    height: 50,
                    decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      color: UiUtils.getColorFromHexValue(
                        assignment.subject.bgColor,
                      ),
                    ),
                    child: CustomImageWidget(
                      imagePath: assignment.subject.image,
                      boxFit: BoxFit.scaleDown,
                    ),
                  ),
                  const SizedBox(width: 10),
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Text(
                        assignment.name,
                        textAlign: TextAlign.center,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: TextStyle(
                          color: UiUtils.getColorScheme(context).secondary,
                          fontWeight: FontWeight.w600,
                          fontSize: 14,
                        ),
                      ),
                      const SizedBox(height: 5),
                      Text(
                        UiUtils.formatAssignmentDueDateDayOnly(
                              assignment.dueDate,
                              context,
                            ) +
                            (assignment.points != 0
                                ? ' | '
                                      '${assignment.points} ${UiUtils.getTranslatedLabel(context, pointsKey)}'
                                : ''),
                        textAlign: TextAlign.center,
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                        style: TextStyle(
                          color: Theme.of(
                            context,
                          ).colorScheme.onSurface.withValues(alpha: 0.75),
                          fontSize: 12,
                        ),
                      ),
                    ],
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
