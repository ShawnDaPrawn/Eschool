import 'package:eschool/data/models/subject.dart';
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
  List<ExamTimeTable>? timetable;

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
    id = json['id'];
    totalMarks = json['total_marks'] ?? 0;
    passingMarks = json['passing_marks'] ?? 0;
    date = json['date'] ?? '';
    startingTime = json['starting_time'] ?? '';
    endingTime = json['ending_time'] ?? '';
    subject = Subject.fromJson(json['subject']);
  }
  int? id;
  int? totalMarks;
  int? passingMarks;
  String? date;
  String? startingTime;
  String? endingTime;
  Subject? subject;
}
