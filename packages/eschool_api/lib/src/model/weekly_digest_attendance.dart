//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'weekly_digest_attendance.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WeeklyDigestAttendance {
  /// Returns a new [WeeklyDigestAttendance] instance.
  WeeklyDigestAttendance({
    required this.recorded,

    required this.present,

    required this.absent,

    required this.excused,
  });

  // minimum: 0
  @JsonKey(name: r'recorded', required: true, includeIfNull: false)
  final int recorded;

  // minimum: 0
  @JsonKey(name: r'present', required: true, includeIfNull: false)
  final int present;

  // minimum: 0
  @JsonKey(name: r'absent', required: true, includeIfNull: false)
  final int absent;

  // minimum: 0
  @JsonKey(name: r'excused', required: true, includeIfNull: false)
  final int excused;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WeeklyDigestAttendance &&
          other.recorded == recorded &&
          other.present == present &&
          other.absent == absent &&
          other.excused == excused;

  @override
  int get hashCode =>
      recorded.hashCode + present.hashCode + absent.hashCode + excused.hashCode;

  factory WeeklyDigestAttendance.fromJson(Map<String, dynamic> json) =>
      _$WeeklyDigestAttendanceFromJson(json);

  Map<String, dynamic> toJson() => _$WeeklyDigestAttendanceToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
