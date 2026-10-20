import 'package:eschool_teacher/data/models/attendanceEnums.dart';
import 'package:eschool_teacher/data/models/student.dart';
import 'package:eschool_teacher/ui/widgets/customUserProfileImageWidget.dart';
import 'package:eschool_teacher/utils/labelKeys.dart';
import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:flutter/material.dart';
import 'package:eschool_teacher/ui/styles/colors.dart';

class StudentAttendanceTileContainer extends StatelessWidget {
  const StudentAttendanceTileContainer({
    required this.attendanceType,
    required this.student,
    required this.updateAttendance,
    required this.isOnLeave,
    required this.leaveType,
    super.key,
  });
  final Student student;
  final Function(int, AttendanceType?) updateAttendance;
  final AttendanceType? attendanceType;
  final bool isOnLeave;
  final AttendanceLeaveType? leaveType;

  AttendanceType _getValidAttendanceType() {
    if (leaveType == AttendanceLeaveType.full) return AttendanceType.absent;
    if (leaveType == AttendanceLeaveType.firstHalf &&
        attendanceType != AttendanceType.absent &&
        attendanceType != AttendanceType.firstHalfAbsent) {
      return AttendanceType.firstHalfAbsent;
    }
    if (leaveType == AttendanceLeaveType.secondHalf &&
        attendanceType != AttendanceType.absent &&
        attendanceType != AttendanceType.secondHalfAbsent) {
      return AttendanceType.secondHalfAbsent;
    }
    return attendanceType ?? AttendanceType.present;
  }

  Widget _buildAttendanceButton({
    required BuildContext context,
    required String labelKey,
    required AttendanceType type,
    required Color color,
    required AttendanceType currentSelectedType,
  }) {
    final isSelected = currentSelectedType == type;

    return Expanded(
      child: GestureDetector(
        onTap: () {
          if (leaveType == AttendanceLeaveType.full) {
            return; // Cannot change full day leave
          }
          updateAttendance(student.id, type);
        },
        child: Container(
          height: 36,
          decoration: BoxDecoration(
            color: isSelected ? color : Theme.of(context).colorScheme.surface,
            borderRadius: BorderRadius.circular(10),
            border: isSelected
                ? null
                : Border.all(
                    color: Theme.of(
                      context,
                    ).colorScheme.secondary.withValues(alpha: 0.1),
                    width: 1,
                  ),
          ),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              if (isSelected) ...[
                const Icon(Icons.check_circle, color: Colors.white, size: 16),
                const SizedBox(width: 4),
              ],
              Flexible(
                child: Text(
                  UiUtils.getTranslatedLabel(context, labelKey),
                  style: TextStyle(
                    color: isSelected
                        ? Colors.white
                        : Theme.of(context).colorScheme.secondary,
                    fontWeight: FontWeight.w600,
                    fontSize: 12,
                  ),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  String _getLeaveBadgeLabel(BuildContext context) {
    if (leaveType == AttendanceLeaveType.full) {
      return UiUtils.getTranslatedLabel(context, fullDayLeaveKey);
    }
    if (leaveType == AttendanceLeaveType.firstHalf) {
      return UiUtils.getTranslatedLabel(context, firstHalfLeaveKey);
    }
    if (leaveType == AttendanceLeaveType.secondHalf) {
      return UiUtils.getTranslatedLabel(context, secondHalfLeaveKey);
    }
    return "";
  }

  Widget _buildLeaveBadge(BuildContext context) {
    if (leaveType == null) return const SizedBox.shrink();

    Color badgeColor;
    if (leaveType == AttendanceLeaveType.full) {
      badgeColor = palletRed;
    } else if (leaveType == AttendanceLeaveType.secondHalf) {
      badgeColor = palletYellow;
    } else {
      badgeColor = palletBlue;
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
      decoration: BoxDecoration(
        color: badgeColor.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(4),
      ),
      child: Text(
        _getLeaveBadgeLabel(context),
        style: TextStyle(
          color: badgeColor,
          fontWeight: FontWeight.w600,
          fontSize: 12,
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final currentType = _getValidAttendanceType();

    return Padding(
      padding: const EdgeInsets.only(bottom: 20),
      child: Container(
        decoration: BoxDecoration(
          boxShadow: [
            BoxShadow(
              color: Theme.of(
                context,
              ).colorScheme.secondary.withValues(alpha: 0.075),
              blurRadius: 10,
              spreadRadius: 2,
            ),
          ],
          color: Theme.of(context).scaffoldBackgroundColor,
          borderRadius: BorderRadius.circular(15),
        ),
        width: MediaQuery.sizeOf(context).width * 0.88,
        padding: const EdgeInsets.all(15),
        child: Column(
          children: [
            // Top section: Image, Name, Roll No, Leave Badge
            Row(
              children: [
                Container(
                  decoration: BoxDecoration(
                    borderRadius: BorderRadius.circular(8),
                  ),
                  height: 40,
                  width: 40,
                  child: CustomUserProfileImageWidget(
                    profileUrl: student.image,
                    radius: BorderRadius.circular(8),
                    color: Colors.black,
                  ),
                ),
                const SizedBox(width: 15),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        student.getFullName(),
                        style: TextStyle(
                          color: Theme.of(context).colorScheme.secondary,
                          fontWeight: FontWeight.w600,
                          fontSize: 14,
                        ),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                      const SizedBox(height: 4),
                      Text(
                        '${UiUtils.getTranslatedLabel(context, rollNoKey)} - ${student.rollNumber}',
                        style: TextStyle(
                          color: Theme.of(
                            context,
                          ).colorScheme.secondary.withValues(alpha: 0.75),
                          fontWeight: FontWeight.w400,
                          fontSize: 12,
                        ),
                        textAlign: TextAlign.start,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: 8),
                _buildLeaveBadge(context),
              ],
            ),
            if (leaveType == null) ...[
              const SizedBox(height: 8),
              Divider(
                color: Theme.of(
                  context,
                ).colorScheme.secondary.withValues(alpha: 0.1),
              ),
              const SizedBox(height: 8),
              // Bottom section: Attendance Action Buttons
              Column(
                spacing: 8,
                children: [
                  Row(
                    spacing: 8,
                    children: [
                      _buildAttendanceButton(
                        context: context,
                        labelKey: presentKey,
                        type: AttendanceType.present,
                        color: palletGreen,
                        currentSelectedType: currentType,
                      ),
                      _buildAttendanceButton(
                        context: context,
                        labelKey: fullDayAbsentKey,
                        type: AttendanceType.absent,
                        color: palletRed,
                        currentSelectedType: currentType,
                      ),
                    ],
                  ),
                  Row(
                    spacing: 8,
                    children: [
                      _buildAttendanceButton(
                        context: context,
                        labelKey: firstHalfAbsentKey,
                        type: AttendanceType.firstHalfAbsent,
                        color: palletBlue,
                        currentSelectedType: currentType,
                      ),
                      _buildAttendanceButton(
                        context: context,
                        labelKey: secondHalfAbsentKey,
                        type: AttendanceType.secondHalfAbsent,
                        color: palletYellow,
                        currentSelectedType: currentType,
                      ),
                    ],
                  ),
                ],
              ),
            ],
          ],
        ),
      ),
    );
  }
}
