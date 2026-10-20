enum AttendanceType {
  absent(0),
  present(1),
  firstHalfAbsent(2),
  secondHalfAbsent(3);

  final int value;
  const AttendanceType(this.value);

  static AttendanceType? fromValue(int? value) {
    if (value == null) return null;
    try {
      return AttendanceType.values.firstWhere((e) => e.value == value);
    } catch (_) {
      return null;
    }
  }
}

enum AttendanceLeaveType {
  full(1),
  firstHalf(2),
  secondHalf(3);

  final int value;
  const AttendanceLeaveType(this.value);

  static AttendanceLeaveType? fromValue(int? value) {
    if (value == null) return null;
    try {
      return AttendanceLeaveType.values.firstWhere((e) => e.value == value);
    } catch (_) {
      return null;
    }
  }
}
