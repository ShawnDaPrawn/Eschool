// ignore_for_file: public_member_api_docs, sort_constructors_first
import 'package:eschool_teacher/data/models/studyMaterial.dart';
import 'package:eschool_teacher/ui/styles/colors.dart';
import 'package:eschool_teacher/utils/labelKeys.dart';
import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';

class Leave {
  final int id;
  final int userId;
  final int leaveMasterId;
  final String reason;
  final String fromDate;
  final String toDate;
  final String status;
  final DateTime createdAt;
  final DateTime updatedAt;
  final double fullLeave;
  final double halfLeave;
  final double days;
  final List<LeaveDetail> leaveDetail;
  final List<StudyMaterial> file;
  final String? rejectionReason;

  Leave({
    required this.id,
    required this.userId,
    required this.leaveMasterId,
    required this.reason,
    required this.fromDate,
    required this.toDate,
    required this.status,
    required this.createdAt,
    required this.updatedAt,
    required this.fullLeave,
    required this.halfLeave,
    required this.days,
    required this.leaveDetail,
    required this.file,
    this.rejectionReason,
  });

  String formattedDateRange({required BuildContext context}) {
    return "${UiUtils.formatStringDate(fromDate)}${fromDate != toDate ? ' ${UiUtils.getTranslatedLabel(context, toKey)} ${UiUtils.formatStringDate(toDate)}' : ''}";
  }

  String get statusKey {
    switch (status) {
      case '1':
        return acceptedKey;
      case '2':
        return rejectedKey;
      default:
        return pendingKey;
    }
  }

  Color get statusColor {
    switch (status) {
      case '1':
        return greenColor;
      case '2':
        return redColor;
      default:
        return orangeColor;
    }
  }

  bool get showDeleteButton {
    return status == '0';
  }

  factory Leave.fromJson(Map<String, dynamic> json) {
    return Leave(
      id: json['id'] ?? 0,
      userId: json['user_id'] ?? 0,
      leaveMasterId: json['leave_master_id'] ?? 0,
      reason: json['reason'] ?? '',
      fromDate: json['from_date'] ?? '',
      toDate: json['to_date'] ?? '',
      status: json['status'] ?? '0',
      createdAt: DateTime.tryParse(json['created_at'] ?? '') ?? DateTime.now(),
      updatedAt: DateTime.tryParse(json['updated_at'] ?? '') ?? DateTime.now(),
      fullLeave: double.tryParse(json['full_leave'].toString()) ?? 0,
      halfLeave: double.tryParse(json['half_leave'].toString()) ?? 0,
      days: double.tryParse(json['days'].toString()) ?? 0,
      leaveDetail: List<LeaveDetail>.from(
        json['leave_detail']?.map((x) => LeaveDetail.fromJson(x)) ?? [],
      ),
      file: List<StudyMaterial>.from(
        json['file']?.map((x) => StudyMaterial.fromJson(x)) ?? [],
      ),
      rejectionReason: json['reason_of_rejection'],
    );
  }
}

class LeaveDetail {
  final int id;
  final int leaveId;
  final String date;
  final LeaveType type;
  final DateTime createdAt;
  final DateTime updatedAt;

  LeaveDetail({
    required this.id,
    required this.leaveId,
    required this.date,
    required this.type,
    required this.createdAt,
    required this.updatedAt,
  });

  factory LeaveDetail.fromJson(Map<String, dynamic> json) {
    return LeaveDetail(
      id: json['id'] ?? 0,
      leaveId: json['leave_id'] ?? 0,
      date: json['date'] ?? '',
      type: LeaveType.fromAPIValue(json['type'] ?? ''),
      createdAt: DateTime.tryParse(json['created_at'] ?? '') ?? DateTime.now(),
      updatedAt: DateTime.tryParse(json['updated_at'] ?? '') ?? DateTime.now(),
    );
  }
}

//Helper models and methods related to add & view leaves
enum LeaveType {
  full,
  firstHalf,
  secondHalf;

  static LeaveType fromAPIValue(String value) {
    switch (value) {
      case 'First Half':
        return LeaveType.firstHalf;
      case 'Second Half':
        return LeaveType.secondHalf;
      default:
        return LeaveType.full;
    }
  }

  String getAPIType() {
    switch (this) {
      case LeaveType.full:
        return 'Full';
      case LeaveType.firstHalf:
        return 'First Half';
      case LeaveType.secondHalf:
        return 'Second Half';
    }
  }

  String getTypeKey() {
    switch (this) {
      case LeaveType.firstHalf:
        return firstHalfKey;
      case LeaveType.secondHalf:
        return secondHalfKey;
      default:
        return fullKey;
    }
  }
}

class Month {
  final int? monthNumber;
  final String nameKey;
  Month({required this.nameKey, required this.monthNumber});

  @override
  bool operator ==(Object other) {
    return (other is Month) && other.monthNumber == monthNumber;
  }

  @override
  int get hashCode => monthNumber.hashCode;
}

List<Month> getAllMonths() {
  return [
    Month(monthNumber: null, nameKey: allMonthsKey),
    Month(monthNumber: 1, nameKey: januaryKey),
    Month(monthNumber: 2, nameKey: februaryKey),
    Month(monthNumber: 3, nameKey: marchKey),
    Month(monthNumber: 4, nameKey: aprilKey),
    Month(monthNumber: 5, nameKey: mayKey),
    Month(monthNumber: 6, nameKey: juneKey),
    Month(monthNumber: 7, nameKey: julyKey),
    Month(monthNumber: 8, nameKey: augustKey),
    Month(monthNumber: 9, nameKey: septemberKey),
    Month(monthNumber: 10, nameKey: octoberKey),
    Month(monthNumber: 11, nameKey: novemberKey),
    Month(monthNumber: 12, nameKey: decemberKey),
  ];
}

Month getCurrentMonth() {
  return getAllMonths()[DateTime.now().month];
}

class LeaveDateWithType {
  DateTime date;
  LeaveType type;
  LeaveDateWithType({required this.date, required this.type});
}
