import 'package:eschool_teacher/data/models/subject.dart';
import 'package:intl/intl.dart';

class Exam {

  Exam({
    this.examID,
    this.examName,
    this.description,
    this.publish,
    this.sessionYear,
    this.examStartingDate,
    this.examEndingDate,
    this.examStatus,
    this.classId,
    this.className,
    this.timetable,
  });

  Exam.fromExamJson(Map<String, dynamic> json) {
    examID = json['id'];
    examName = json['name'] ?? '';
    description = json['description'] ?? '';
    publish = json['publish'] ?? 0;
    sessionYear = json['session_year'] ?? '';
    examStartingDate = json['exam_starting_date'] ?? '';
    examEndingDate = json['exam_ending_date'] ?? '';
    examStatus = json['exam_status'] ?? '';
    classId = json['class_id'] ?? 0;
    className = json['class_name'] ?? '';
    timetable =
        json['exam_timetable'] != null && json['exam_timetable'].isNotEmpty
            ? (json['exam_timetable'] as List)
                .map((e) => ExamTimeTable.fromJson(e))
                .toList()
            : null;
  }
  int? examID;
  String? examName;
  String? description;
  int? publish;
  String? sessionYear;
  String? examStartingDate;
  String? examEndingDate;
  String? examStatus;
  int? classId;
  String? className;
  List<ExamTimeTable>? timetable;

  String getExamName() {
    return '$examName';
  }

  DateTime? getStartDate() {
    if (examStartingDate == null) return null;
    return DateFormat('yyyy-MM-dd').tryParse(examStartingDate!);
  }

  DateTime? getEndDate() {
    if (examEndingDate == null) return null;
    return DateFormat('yyyy-MM-dd').tryParse(examEndingDate!);
  }
}

class ExamTimeTable {

  ExamTimeTable({
    this.id,
    this.totalMarks,
    this.passingMarks,
    this.date,
    this.startingTime,
    this.endingTime,
    this.subject,
  });

  ExamTimeTable.fromJson(Map<String, dynamic> json) {
    id = json['id'] ?? 0;
    totalMarks = json['total_marks'] ?? 0;
    passingMarks = json['passing_marks'] ?? 0;
    date = json['date'] ?? '';
    startingTime = json['starting_time'] ?? '';
    endingTime = json['ending_time'] ?? '';
    subject = Subject.fromJson(json['subject'] ?? {});
  }
  int? id;
  int? totalMarks;
  int? passingMarks;
  String? date;
  String? startingTime;
  String? endingTime;
  Subject? subject;

  String getSubjectDetails() {
    return '$subject';
  }
}
