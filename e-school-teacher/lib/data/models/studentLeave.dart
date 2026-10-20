// ignore_for_file: public_member_api_docs, sort_constructors_first
import 'package:eschool_teacher/data/models/student.dart';
import 'package:eschool_teacher/data/models/studyMaterial.dart';
import 'package:eschool_teacher/ui/styles/colors.dart';
import 'package:eschool_teacher/utils/labelKeys.dart';
import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';

class StudentLeave {
  final int id;
  final int userId;
  final String reason;
  final String fromDate;
  final String toDate;
  final String status;
  final int sessionYearId;
  final DateTime createdAt;
  final DateTime updatedAt;
  final List<LeaveDetail>? leaveDetail;
  final Student? student;
  final List<StudyMaterial> file;
  final double days;

  StudentLeave({
    required this.id,
    required this.userId,
    required this.reason,
    required this.fromDate,
    required this.toDate,
    required this.status,
    required this.createdAt,
    required this.updatedAt,
    required this.leaveDetail,
    required this.file,
    required this.sessionYearId,
    required this.student,
    required this.days,
  });

  factory StudentLeave.fromJson(Map<String, dynamic> json) {
    return StudentLeave(
      id: json['id'] ?? 0,
      userId: json['user_id'] ?? 0,
      reason: json['reason'] ?? '',
      fromDate: json['from_date'] ?? '',
      toDate: json['to_date'] ?? '',
      status: json['status'] ?? '0',
      createdAt: DateTime.tryParse(json['created_at'] ?? '') ?? DateTime.now(),
      updatedAt: DateTime.tryParse(json['updated_at'] ?? '') ?? DateTime.now(),
      days: double.tryParse(json['total_days'].toString()) ?? 0,
      leaveDetail: List<LeaveDetail>.from(
        json['leave_detail']?.map((x) => LeaveDetail.fromJson(x)) ?? [],
      ),
      file: List<StudyMaterial>.from(
        json['file']?.map((x) => StudyMaterial.fromJson(x)) ?? [],
      ),
      sessionYearId: json['session_year_id'] ?? 0,
      student: Student.fromUserJson(
        Map.from(json['user']),
      ),
    );
  }

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

  StudentLeave copyWith({
    String? reason,
    String? fromDate,
    String? toDate,
    String? status,
    DateTime? createdAt,
    DateTime? updatedAt,
    List<LeaveDetail>? leaveDetail,
    List<StudyMaterial>? file,
    int? sessionYearId,
    Student? student,
  }) {
    return StudentLeave(
      id: id,
      userId: userId,
      reason: reason ?? this.reason,
      fromDate: fromDate ?? this.fromDate,
      toDate: toDate ?? this.toDate,
      status: status ?? this.status,
      createdAt: createdAt ?? this.createdAt,
      updatedAt: updatedAt ?? this.updatedAt,
      leaveDetail: leaveDetail ?? this.leaveDetail,
      file: file ?? this.file,
      sessionYearId: sessionYearId ?? this.sessionYearId,
      student: student ?? this.student,
      days: days,
    );
  }
}

class LeaveDetail {
  final int id;
  final int leaveId;
  final String date;
  final String type;
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
      type: json['type'] ?? '',
      createdAt: DateTime.tryParse(json['created_at'] ?? '') ?? DateTime.now(),
      updatedAt: DateTime.tryParse(json['updated_at'] ?? '') ?? DateTime.now(),
    );
  }
}

//Helper models and methods related to add & view leaves
enum LeaveType { full, firstHalf, secondHalf }

extension LeaveTypeHelper on LeaveType {
  String getAPIType() {
    switch (this) {
      case LeaveType.firstHalf:
        return 'First Half';
      case LeaveType.secondHalf:
        return 'Second Half';
      default:
        return 'Full';
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

class LeaveDateWithType {
  DateTime date;
  LeaveType type;
  LeaveDateWithType({
    required this.date,
    required this.type,
  });
}
