class AssignmentReport {
  final num averageCompletionRate;
  final int totalAssignments;
  final int passCount;
  final int pendingCount;
  final int missedCount;
  final int onTimeSubmission;
  final int lateSubmission;

  AssignmentReport({
    required this.averageCompletionRate,
    required this.totalAssignments,
    required this.passCount,
    required this.pendingCount,
    required this.missedCount,
    required this.onTimeSubmission,
    required this.lateSubmission,
  });

  factory AssignmentReport.fromJson(Map<String, dynamic> json) {
    return AssignmentReport(
      averageCompletionRate: num.tryParse(json['average_completion_rate']?.toString() ?? '0') ?? 0,
      totalAssignments: json['total_assignments'] ?? 0,
      passCount: json['pass_count'] ?? 0,
      pendingCount: json['pending_count'] ?? 0,
      missedCount: json['missed_count'] ?? 0,
      onTimeSubmission: json['on_time_submission'] ?? 0,
      lateSubmission: json['late_submission'] ?? 0,
    );
  }
}
