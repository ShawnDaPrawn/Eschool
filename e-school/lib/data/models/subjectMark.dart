class SubjectMark {

  SubjectMark({
    required this.grade,
    required this.obtainedMarks,
    required this.subjectName,
    required this.subjectType,
    required this.passingMarks,
    required this.totalMarks,
  });

  factory SubjectMark.fromJson(Map<String, dynamic> json) {
    return SubjectMark(
      grade: json['grade'] ?? '',
      obtainedMarks: json['obtained_marks'] ?? 0,
      totalMarks: json['total_marks'] ?? 0,
      subjectName: json['subject_name'] ?? '',
      subjectType: json['subject_type'] ?? '',
      passingMarks: json['passing_marks'] ?? 0,
    );
  }
  // final Subject subject;
  final int totalMarks;
  final String subjectName;
  final String subjectType;
  final int obtainedMarks;
  final String grade;
  final int passingMarks;
}
