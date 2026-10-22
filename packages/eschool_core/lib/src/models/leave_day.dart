import 'package:eschool_core/src/l10n/label_keys.dart';

/// How much of a day a leave covers.
enum LeaveType {
  full,
  firstHalf,
  secondHalf;

  static LeaveType fromAPIValue(String value) {
    switch (value) {
      case 'First Half':
        return LeaveType.firstHalf;
      case 'Second Half':
        return LeaveType.secondHalf;
      default:
        return LeaveType.full;
    }
  }
}

extension LeaveTypeHelper on LeaveType {
  String getAPIType() {
    switch (this) {
      case LeaveType.firstHalf:
        return 'First Half';
      case LeaveType.secondHalf:
        return 'Second Half';
      case LeaveType.full:
        return 'Full';
    }
  }

  String getTypeKey() {
    switch (this) {
      case LeaveType.firstHalf:
        return firstHalfKey;
      case LeaveType.secondHalf:
        return secondHalfKey;
      case LeaveType.full:
        return fullKey;
    }
  }
}

/// One day of a leave being applied for, and how much of it is off.
class LeaveDateWithType {
  DateTime date;
  LeaveType type;
  LeaveDateWithType({required this.date, required this.type});
}
