import 'package:eschool_teacher/data/models/attendanceEnums.dart';

class AttendanceReport {
  AttendanceReport({
    required this.id,
    required this.classSectionId,
    required this.studentId,
    required this.sessionYearId,
    required this.type,
    required this.date,
    required this.remark,
  });

  AttendanceReport.fromJson(Map<String, dynamic> json) {
    id = json['id'] ?? 0;
    classSectionId = json['class_section_id'] ?? 0;
    studentId = json['student_id'] ?? 0;
    sessionYearId = json['session_year_id'] ?? 0;
    type = json['type'] ?? -1;
    attendanceType = AttendanceType.fromValue(type);
    date = json['date'] == null ? DateTime.now() : DateTime.parse(json['date']);
    remark = json['remark'] ?? '';
  }
  late final int id;
  late final int classSectionId;
  late final int studentId;
  late final int sessionYearId;
  late final int type;
  late final AttendanceType? attendanceType;
  late final DateTime date;
  late final String remark;

  bool isPresent() {
    return type == 1;
  }

  bool isAbsent() {
    return type == 0;
  }

  bool isFirstHalfAbsent() {
    return type == 2;
  }

  bool isSecondHalfAbsent() {
    return type == 3;
  }
}
