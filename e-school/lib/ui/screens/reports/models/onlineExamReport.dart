class OnlineExamReport {
  final num averagePercentage;
  final int totalExams;
  final num attemptedPercentage;
  final int attemptedCount;
  final int missedCount;
  final String? bestExamName;
  final num bestExamPercentage;
  final String? worstExamName;
  final num worstExamPercentage;

  OnlineExamReport({
    required this.averagePercentage,
    required this.totalExams,
    required this.attemptedPercentage,
    required this.attemptedCount,
    required this.missedCount,
    this.bestExamName,
    required this.bestExamPercentage,
    this.worstExamName,
    required this.worstExamPercentage,
  });

  factory OnlineExamReport.fromJson(Map<String, dynamic> json) {
    return OnlineExamReport(
      averagePercentage: num.tryParse(json['average_percentage']?.toString() ?? '0') ?? 0,
      totalExams: json['total_exams'] ?? 0,
      attemptedPercentage: num.tryParse(json['attempted_percentage']?.toString() ?? '0') ?? 0,
      attemptedCount: json['attempted_count'] ?? 0,
      missedCount: json['missed_count'] ?? 0,
      bestExamName: json['best_exam_name'],
      bestExamPercentage: num.tryParse(json['best_exam_percentage']?.toString() ?? '0') ?? 0,
      worstExamName: json['worst_exam_name'],
      worstExamPercentage: num.tryParse(json['worst_exam_percentage']?.toString() ?? '0') ?? 0,
    );
  }
}
