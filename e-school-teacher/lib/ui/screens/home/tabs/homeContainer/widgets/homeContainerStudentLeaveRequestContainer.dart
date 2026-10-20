import 'package:eschool_teacher/cubits/dashboardCubit.dart';
import 'package:eschool_teacher/cubits/updateStudentLeaveStatusCubit.dart';
import 'package:eschool_teacher/data/models/studentLeave.dart';
import 'package:eschool_teacher/ui/screens/studentLeaves/widgets/studentLeaveDetailsBottomsheetContainer.dart';
import 'package:eschool_teacher/ui/widgets/customImageWidget.dart';
import 'package:eschool_teacher/utils/labelKeys.dart';
import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

class HomeContainerStudentLeaveRequestContainer extends StatelessWidget {
  const HomeContainerStudentLeaveRequestContainer({
    required this.studentLeave, super.key,
  });
  final StudentLeave studentLeave;

  @override
  Widget build(BuildContext context) {
    return BlocListener<UpdateStudentLeaveStatusCubit,
        UpdateStudentLeaveStatusState>(
      listener: (context, state) {
        //remove from here if accepted or rejected
        if (state is UpdateStudentLeaveStatusSuccess) {
          if (!state.isStatusPending && state.id == studentLeave.id) {
            context
                .read<DashboardCubit>()
                .removeStudentLeaveRequest(studentLeave.id);
          }
        }
      },
      child: Container(
        padding: const EdgeInsets.all(14),
        width: 334,
        decoration: BoxDecoration(
          color: Theme.of(context).scaffoldBackgroundColor,
          borderRadius: BorderRadius.circular(8),
          border: Border.all(
            color:
                Theme.of(context).colorScheme.secondary.withValues(alpha: 0.25),
          ),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Row(
              children: [
                Container(
                  height: 50,
                  width: 50,
                  decoration: const BoxDecoration(shape: BoxShape.circle),
                  clipBehavior: Clip.antiAlias,
                  child: CustomImageWidget(
                    imagePath: studentLeave.student?.image ?? '',
                  ),
                ),
                const SizedBox(
                  width: 8,
                ),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      Row(
                        children: [
                          Expanded(
                            child: Text(
                              studentLeave.student?.getFullName() ?? '',
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: TextStyle(
                                color: Theme.of(context).colorScheme.secondary,
                                fontSize: 16,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          ),
                          const SizedBox(
                            width: 6,
                          ),
                          Container(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 8,
                              vertical: 4,
                            ),
                            decoration: BoxDecoration(
                              color: studentLeave.statusColor
                                  .withValues(alpha: .1),
                              borderRadius: BorderRadius.circular(4),
                            ),
                            child: Text(
                              UiUtils.getTranslatedLabel(context, pendingKey),
                              style: TextStyle(
                                color: studentLeave.statusColor,
                                fontWeight: FontWeight.w500,
                              ),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(
                        height: 4,
                      ),
                      Text(
                        '${UiUtils.getTranslatedLabel(context, classKey)} : ${studentLeave.student?.classSectionName}',
                        textAlign: TextAlign.start,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: TextStyle(
                          color: Theme.of(context).colorScheme.onSurface,
                          fontSize: 14,
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
            const Spacer(),
            Divider(
              color: Theme.of(context)
                  .colorScheme
                  .secondary
                  .withValues(alpha: 0.25),
            ),
            const Spacer(
              flex: 3,
            ),
            Row(
              children: [
                Expanded(
                  child: Text(
                    "${UiUtils.getTranslatedLabel(context, totalDaysKey)} : ${studentLeave.days.toString().endsWith('.0') ? studentLeave.days.toStringAsFixed(0).padLeft(2, '0') : studentLeave.days.toStringAsFixed(1)}",
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(
                      color: Theme.of(context).colorScheme.onSurface,
                      fontWeight: FontWeight.w500,
                      fontSize: 14,
                    ),
                  ),
                ),
                const SizedBox(width: 8),
                InkWell(
                  onTap: () {
                    UiUtils.showBottomSheet(
                      child: BlocProvider.value(
                        value: context.read<UpdateStudentLeaveStatusCubit>(),
                        child: StudentLeaveDetailsBottomsheetContainer(
                          leave: studentLeave,
                        ),
                      ),
                      context: context,
                    );
                  },
                  borderRadius: BorderRadius.circular(4),
                  child: Container(
                    height: 32,
                    alignment: Alignment.center,
                    decoration: BoxDecoration(
                      borderRadius: BorderRadius.circular(4),
                      border: Border.all(
                        color: Theme.of(context)
                            .colorScheme
                            .secondary
                            .withValues(alpha: 0.25),
                      ),
                    ),
                    padding:
                        const EdgeInsets.symmetric(vertical: 4, horizontal: 8),
                    child: Row(
                      children: [
                        Text(
                          UiUtils.getTranslatedLabel(context, viewMoreKey),
                          style: TextStyle(
                            color: Theme.of(context).colorScheme.secondary,
                          ),
                        ),
                        const SizedBox(
                          width: 4,
                        ),
                        Icon(
                          Icons.arrow_right_alt,
                          color: Theme.of(context).colorScheme.secondary,
                          size: 22,
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}
