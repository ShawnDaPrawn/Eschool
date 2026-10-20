class FeesReport {
  final num totalFeePayable;
  final num totalPaid;
  final num totalPending;
  final String? lastPaymentDate;
  final String? nextDueDate;
  final FeesInstalmentSummary instalments;

  FeesReport({
    required this.totalFeePayable,
    required this.totalPaid,
    required this.totalPending,
    this.lastPaymentDate,
    this.nextDueDate,
    required this.instalments,
  });

  factory FeesReport.fromJson(Map<String, dynamic> json) {
    return FeesReport(
      totalFeePayable: num.tryParse(json['total_fee_payable']?.toString() ?? '0') ?? 0,
      totalPaid: num.tryParse(json['total_paid']?.toString() ?? '0') ?? 0,
      totalPending: num.tryParse(json['total_pending']?.toString() ?? '0') ?? 0,
      lastPaymentDate: json['last_payment_date'],
      nextDueDate: json['next_due_date'],
      instalments: json['instalments'] != null
          ? FeesInstalmentSummary.fromJson(json['instalments'])
          : FeesInstalmentSummary(total: 0, completed: 0, pending: 0),
    );
  }
}

class FeesInstalmentSummary {
  final int total;
  final int completed;
  final int pending;

  FeesInstalmentSummary({
    required this.total,
    required this.completed,
    required this.pending,
  });

  factory FeesInstalmentSummary.fromJson(Map<String, dynamic> json) {
    return FeesInstalmentSummary(
      total: json['total'] ?? 0,
      completed: json['completed'] ?? 0,
      pending: json['pending'] ?? 0,
    );
  }
}
