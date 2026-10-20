import 'package:eschool_teacher/data/models/leave.dart';
import 'package:eschool_teacher/ui/screens/leave/widgets/customRadioContainer.dart';
import 'package:eschool_teacher/utils/labelKeys.dart';
import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:flutter/material.dart';

class OneDayLeaveStatusContainer extends StatefulWidget {
  const OneDayLeaveStatusContainer({required this.leaveDayDetails, super.key});
  final LeaveDateWithType leaveDayDetails;

  @override
  State<OneDayLeaveStatusContainer> createState() =>
      _OneDayLeaveStatusContainerState();
}

class _OneDayLeaveStatusContainerState
    extends State<OneDayLeaveStatusContainer> {
  String getWeekdayNameKey(int weekday) {
    switch (weekday) {
      case DateTime.monday:
        return mondayKey;
      case DateTime.tuesday:
        return tuesdayKey;
      case DateTime.wednesday:
        return wednesdayKey;
      case DateTime.thursday:
        return thursdayKey;
      case DateTime.friday:
        return fridayKey;
      case DateTime.saturday:
        return saturdayKey;
      case DateTime.sunday:
        return sundayKey;
      default:
        return unknownKey;
    }
  }

  Widget _buildLeaveTypePicker(LeaveType type) {
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: () {
          setState(() {
            widget.leaveDayDetails.type = type;
          });
        },
        borderRadius: BorderRadius.circular(8),
        child: Container(
          height: MediaQuery.sizeOf(context).width * .27,
          decoration: BoxDecoration(
            border: Border.all(
              color: type == widget.leaveDayDetails.type
                  ? Theme.of(context).colorScheme.primary
                  : Theme.of(
                      context,
                    ).colorScheme.secondary.withValues(alpha: 0.5),
            ),
            borderRadius: BorderRadius.circular(8),
            color: type == widget.leaveDayDetails.type
                ? Theme.of(context).colorScheme.primary.withValues(alpha: 0.1)
                : Theme.of(
                    context,
                  ).colorScheme.secondary.withValues(alpha: 0.07),
          ),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.spaceEvenly,
            children: [
              CustomRadioContainer(
                isSelected: type == widget.leaveDayDetails.type,
              ),
              Text(
                UiUtils.getTranslatedLabel(context, type.getTypeKey()),
                style: TextStyle(
                  color: type == widget.leaveDayDetails.type
                      ? Theme.of(context).colorScheme.primary
                      : Theme.of(context).colorScheme.secondary,
                  fontWeight: FontWeight.w500,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        const SizedBox(height: 20),
        Row(
          children: [
            Container(
              decoration: BoxDecoration(
                color: Theme.of(context).colorScheme.primary,
                borderRadius: BorderRadius.circular(4),
              ),
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
              child: Text(
                UiUtils.getTranslatedLabel(
                  context,
                  getWeekdayNameKey(widget.leaveDayDetails.date.weekday),
                ),
                style: TextStyle(
                  color: Theme.of(context).scaffoldBackgroundColor,
                  fontWeight: FontWeight.w600,
                ),
              ),
            ),
            const Spacer(),
            Icon(
              Icons.calendar_month_outlined,
              color: Theme.of(
                context,
              ).colorScheme.secondary.withValues(alpha: 0.6),
            ),
            const SizedBox(width: 5),
            Text(
              UiUtils.formatDate(widget.leaveDayDetails.date),
              style: TextStyle(
                color: Theme.of(
                  context,
                ).colorScheme.secondary.withValues(alpha: 0.6),
                fontWeight: FontWeight.w600,
              ),
            ),
          ],
        ),
        const SizedBox(height: 10),
        Row(
          children: [
            Expanded(child: _buildLeaveTypePicker(LeaveType.full)),
            const SizedBox(width: 10),
            Expanded(child: _buildLeaveTypePicker(LeaveType.firstHalf)),
            const SizedBox(width: 10),
            Expanded(child: _buildLeaveTypePicker(LeaveType.secondHalf)),
          ],
        ),
      ],
    );
  }
}
