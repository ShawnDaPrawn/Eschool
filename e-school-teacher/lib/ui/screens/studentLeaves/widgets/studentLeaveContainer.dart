import 'package:eschool_teacher/cubits/updateStudentLeaveStatusCubit.dart';
import 'package:eschool_teacher/data/models/studentLeave.dart';
import 'package:eschool_teacher/ui/screens/studentLeaves/widgets/studentLeaveDetailsBottomsheetContainer.dart';
import 'package:eschool_teacher/utils/labelKeys.dart';
import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

class StudentLeaveContainer extends StatelessWidget {
  const StudentLeaveContainer({
    required this.leave,
    required this.index,
    super.key,
  });
  final StudentLeave leave;
  final int index;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(15),
      margin: const EdgeInsets.symmetric(vertical: 5),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.circular(8),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        mainAxisSize: MainAxisSize.min,
        children: [
          Row(
            children: [
              Container(
                width: 50,
                height: 50,
                decoration: BoxDecoration(
                  image: DecorationImage(
                    image: NetworkImage(leave.student!.image),
                  ),
                  shape: BoxShape.circle,
                  color: Theme.of(
                    context,
                  ).colorScheme.primary.withValues(alpha: .1),
                ),
                padding: const EdgeInsets.all(8),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      '${leave.student!.getFullName()} ',
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: TextStyle(
                        color: Theme.of(context).colorScheme.secondary,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                    Text(
                      '${UiUtils.getTranslatedLabel(context, rollNoKey)} - ${leave.student!.rollNumber}',
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: TextStyle(
                        color: Theme.of(context).colorScheme.onSurface,
                        fontSize: 11,
                        fontWeight: FontWeight.w400,
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 5),
              Container(
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(4),
                  color: leave.statusColor.withValues(alpha: .1),
                ),
                padding: const EdgeInsets.symmetric(vertical: 4, horizontal: 8),
                child: Text(
                  UiUtils.getTranslatedLabel(context, leave.statusKey),
                  style: TextStyle(
                    color: leave.statusColor,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 5),
          Divider(
            color: Theme.of(
              context,
            ).colorScheme.secondary.withValues(alpha: .1),
          ),
          const SizedBox(height: 5),
          Text(
            UiUtils.getTranslatedLabel(context, leaveReasonKey),
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: TextStyle(
              color: Theme.of(context).colorScheme.secondary,
              fontWeight: FontWeight.w600,
            ),
          ),
          const SizedBox(height: 5),
          Text(
            leave.reason,
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
            style: TextStyle(
              color: Theme.of(
                context,
              ).colorScheme.secondary.withValues(alpha: 0.76),
            ),
          ),
          const SizedBox(height: 5),
          Divider(
            color: Theme.of(
              context,
            ).colorScheme.secondary.withValues(alpha: .1),
          ),
          const SizedBox(height: 5),
          Row(
            children: [
              Expanded(
                child: Text(
                  "${UiUtils.getTranslatedLabel(context, totalDaysKey)} : ${leave.days.toString().endsWith('.0') ? leave.days.toStringAsFixed(0).padLeft(2, '0') : leave.days.toStringAsFixed(1)}",
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(
                    color: Theme.of(context).colorScheme.secondary,
                    fontWeight: FontWeight.w600,
                    fontSize: 16,
                  ),
                ),
              ),
              const SizedBox(width: 10),
              InkWell(
                onTap: () {
                  UiUtils.showBottomSheet(
                    child: BlocProvider.value(
                      value: context.read<UpdateStudentLeaveStatusCubit>(),
                      child: StudentLeaveDetailsBottomsheetContainer(
                        leave: leave,
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
                    color: Theme.of(context).colorScheme.primary,
                  ),
                  padding: const EdgeInsets.symmetric(
                    vertical: 4,
                    horizontal: 8,
                  ),
                  child: Text(
                    UiUtils.getTranslatedLabel(context, viewMoreKey),
                    style: TextStyle(
                      color: Theme.of(context).scaffoldBackgroundColor,
                    ),
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}
