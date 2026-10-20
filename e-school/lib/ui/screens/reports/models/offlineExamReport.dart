class OfflineExamReport {
  final num averagePercentage;
  final String avgGrade;
  final int totalExams;
  final num passPercentage;
  final int passCount;
  final int failCount;
  final int missedCount;
  final String? bestExamName;
  final num bestExamPercentage;
  final String? worstExamName;
  final num worstExamPercentage;

  OfflineExamReport({
    required this.averagePercentage,
    required this.avgGrade,
    required this.totalExams,
    required this.passPercentage,
    required this.passCount,
    required this.failCount,
    required this.missedCount,
    this.bestExamName,
    required this.bestExamPercentage,
    this.worstExamName,
    required this.worstExamPercentage,
  });

  factory OfflineExamReport.fromJson(Map<String, dynamic> json) {
    return OfflineExamReport(
      averagePercentage: num.tryParse(json['average_percentage']?.toString() ?? '0') ?? 0,
      avgGrade: json['avg_grade'] ?? '',
      totalExams: json['total_exams'] ?? 0,
      passPercentage: num.tryParse(json['pass_percentage']?.toString() ?? '0') ?? 0,
      passCount: json['pass_count'] ?? 0,
      failCount: json['fail_count'] ?? 0,
      missedCount: json['missed_count'] ?? 0,
      bestExamName: json['best_exam_name'],
      bestExamPercentage: num.tryParse(json['best_exam_percentage']?.toString() ?? '0') ?? 0,
      worstExamName: json['worst_exam_name'],
      worstExamPercentage: num.tryParse(json['worst_exam_percentage']?.toString() ?? '0') ?? 0,
    );
  }
}
