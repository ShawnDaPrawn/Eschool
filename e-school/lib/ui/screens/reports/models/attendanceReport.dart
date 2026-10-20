class AttendanceReport {
  final num totalPresent;
  final num totalAbsent;
  final int fullDayPresent;
  final int fullDayAbsent;
  final int firstHalfAbsent;
  final int secondHalfAbsent;
  final List<AttendanceEntry> attendance;

  AttendanceReport({
    required this.totalPresent,
    required this.totalAbsent,
    required this.fullDayPresent,
    required this.fullDayAbsent,
    required this.firstHalfAbsent,
    required this.secondHalfAbsent,
    required this.attendance,
  });

  factory AttendanceReport.fromJson(Map<String, dynamic> json) {
    return AttendanceReport(
      totalPresent: json['total_present'] ?? 0,
      totalAbsent: json['total_absent'] ?? 0,
      fullDayPresent: json['full_day_present'] ?? 0,
      fullDayAbsent: json['full_day_absent'] ?? 0,
      firstHalfAbsent: json['first_half_absent'] ?? 0,
      secondHalfAbsent: json['second_half_absent'] ?? 0,
      attendance:
          (json['attendance'] as List<dynamic>?)
              ?.map((e) => AttendanceEntry.fromJson(e))
              .toList() ??
          [],
    );
  }
}

class AttendanceEntry {
  final String date;
  final int type;

  AttendanceEntry({required this.date, required this.type});

  factory AttendanceEntry.fromJson(Map<String, dynamic> json) {
    return AttendanceEntry(date: json['date'] ?? '', type: json['type'] ?? 0);
  }
}
