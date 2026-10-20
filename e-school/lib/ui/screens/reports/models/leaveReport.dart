class LeaveReport {
  final num totalLeaves;
  final num completed;
  final num pending;
  final num rejected;

  LeaveReport({
    required this.totalLeaves,
    required this.completed,
    required this.pending,
    required this.rejected,
  });

  factory LeaveReport.fromJson(Map<String, dynamic> json) {
    return LeaveReport(
      totalLeaves: json['total_leaves'] ?? 0,
      completed: json['completed'] ?? 0,
      pending: json['pending'] ?? 0,
      rejected: json['rejected'] ?? 0,
    );
  }
}
