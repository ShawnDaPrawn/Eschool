enum TeacherAttendanceType {
  fullDayPresent(0),
  fullDayAbsent(1),
  firstHalfAbsent(2),
  secondHalfAbsent(3);

  final int value;

  const TeacherAttendanceType(this.value);

  factory TeacherAttendanceType.fromInt(int value) {
    switch (value) {
      case 0:
        return TeacherAttendanceType.fullDayPresent;
      case 1:
        return TeacherAttendanceType.fullDayAbsent;
      case 2:
        return TeacherAttendanceType.firstHalfAbsent;
      case 3:
        return TeacherAttendanceType.secondHalfAbsent;
      default:
        return TeacherAttendanceType.fullDayPresent; // Default or fallback
    }
  }
}

class TeacherAttendanceDay {
  final DateTime date;
  final TeacherAttendanceType type;

  TeacherAttendanceDay({required this.date, required this.type});

  factory TeacherAttendanceDay.fromJson(Map<String, dynamic> json) {
    return TeacherAttendanceDay(
      date: DateTime.parse(json['date'] as String),
      type: TeacherAttendanceType.fromInt(
        int.parse(json['type']?.toString() ?? '0'),
      ),
    );
  }
}

class TeacherAttendanceReport {
  final num totalPresent;
  final num totalAbsent;
  final int fullDayPresent;
  final int fullDayAbsent;
  final num firstHalfAbsent;
  final num secondHalfAbsent;
  final List<TeacherAttendanceDay> attendance;

  TeacherAttendanceReport({
    required this.totalPresent,
    required this.totalAbsent,
    required this.fullDayPresent,
    required this.fullDayAbsent,
    required this.firstHalfAbsent,
    required this.secondHalfAbsent,
    required this.attendance,
  });

  factory TeacherAttendanceReport.fromJson(Map<String, dynamic> json) {
    return TeacherAttendanceReport(
      totalPresent: num.parse(json['total_present']?.toString() ?? '0'),
      totalAbsent: num.parse(json['total_absent']?.toString() ?? '0'),
      fullDayPresent: int.parse(json['full_day_present']?.toString() ?? '0'),
      fullDayAbsent: int.parse(json['full_day_absent']?.toString() ?? '0'),
      firstHalfAbsent: num.parse(json['first_half_absent']?.toString() ?? '0'),
      secondHalfAbsent: num.parse(
        json['second_half_absent']?.toString() ?? '0',
      ),
      attendance: (json['attendance'] as List? ?? [])
          .map((e) => TeacherAttendanceDay.fromJson(e as Map<String, dynamic>))
          .toList(),
    );
  }
}
