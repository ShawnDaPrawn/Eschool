import 'package:eschool/data/models/subject.dart';

class ExamsOnline {
  ExamsOnline({
    required this.id,
    required this.title,
    required this.startDate,
    required this.endDate,
    required this.duration,
    required this.totalMarks,
    required this.subject,
    this.examKey,
  });

  ExamsOnline.fromJson(Map<String, dynamic> json) {
    id = json['exam_id'];
    title = json['title'];
    //The server no longer sends the exam key, students have to enter it
    examKey = int.tryParse(json['exam_key']?.toString() ?? '');
    duration = json['duration'];
    startDate = json['start_date'];
    endDate = json['end_date'];
    totalMarks = json['total_marks'] ?? '';
    subject = Subject.fromJson(json['subject']);
  }
  late final int id;
  late final String title;
  late final String startDate;
  late final String endDate;
  late final int? examKey;
  late final int duration;
  late final String totalMarks;
  late final Subject subject;
}
