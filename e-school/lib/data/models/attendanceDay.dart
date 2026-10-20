enum AttendanceType {
  fullDayPresent(0),
  fullDayAbsent(1),
  firstHalfAbsent(2),
  secondHalfAbsent(3);

  final int value;

  const AttendanceType(this.value);

  factory AttendanceType.fromInt(int value) {
    switch (value) {
      case 0:
        return AttendanceType.fullDayPresent;
      case 1:
        return AttendanceType.fullDayAbsent;
      case 2:
        return AttendanceType.firstHalfAbsent;
      case 3:
        return AttendanceType.secondHalfAbsent;
      default:
        return AttendanceType.fullDayPresent;
    }
  }
}

class AttendanceDay {
  final DateTime date;
  final AttendanceType type;

  AttendanceDay({required this.date, required this.type});

  factory AttendanceDay.fromJson(Map<String, dynamic> json) {
    return AttendanceDay(
      date: DateTime.parse(json['date'] as String),
      type: AttendanceType.fromInt(int.parse(json['type']?.toString() ?? '0')),
    );
  }
}

class AttendanceReport {
  final num totalPresent;
  final num totalAbsent;
  final int fullDayPresent;
  final int fullDayAbsent;
  final num firstHalfAbsent;
  final num secondHalfAbsent;
  final List<AttendanceDay> attendance;

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
      totalPresent: num.tryParse(json['total_present']?.toString() ?? '0') ?? 0,
      totalAbsent: num.tryParse(json['total_absent']?.toString() ?? '0') ?? 0,
      fullDayPresent:
          int.tryParse(json['full_day_present']?.toString() ?? '0') ?? 0,
      fullDayAbsent:
          int.tryParse(json['full_day_absent']?.toString() ?? '0') ?? 0,
      firstHalfAbsent:
          num.tryParse(json['first_half_absent']?.toString() ?? '0') ?? 0,
      secondHalfAbsent:
          num.tryParse(json['second_half_absent']?.toString() ?? '0') ?? 0,
      attendance: (json['attendance'] as List? ?? [])
          .map((e) => AttendanceDay.fromJson(e as Map<String, dynamic>))
          .toList(),
    );
  }
}
