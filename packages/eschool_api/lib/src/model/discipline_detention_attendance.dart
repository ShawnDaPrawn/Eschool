//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'discipline_detention_attendance.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class DisciplineDetentionAttendance {
  /// Returns a new [DisciplineDetentionAttendance] instance.
  DisciplineDetentionAttendance({
    required this.sessionId,

    required this.entryId,

    required this.sessionRevision,

    required this.entryRevision,

    required this.attendance,
  });

  // minimum: 1
  @JsonKey(name: r'session_id', required: true, includeIfNull: false)
  final int sessionId;

  // minimum: 1
  @JsonKey(name: r'entry_id', required: true, includeIfNull: false)
  final int entryId;

  // minimum: 1
  @JsonKey(name: r'session_revision', required: true, includeIfNull: false)
  final int sessionRevision;

  // minimum: 1
  @JsonKey(name: r'entry_revision', required: true, includeIfNull: false)
  final int entryRevision;

  @JsonKey(
    name: r'attendance',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        DisciplineDetentionAttendanceAttendanceEnum.unknownDefaultOpenApi,
  )
  final DisciplineDetentionAttendanceAttendanceEnum attendance;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is DisciplineDetentionAttendance &&
          other.sessionId == sessionId &&
          other.entryId == entryId &&
          other.sessionRevision == sessionRevision &&
          other.entryRevision == entryRevision &&
          other.attendance == attendance;

  @override
  int get hashCode =>
      sessionId.hashCode +
      entryId.hashCode +
      sessionRevision.hashCode +
      entryRevision.hashCode +
      attendance.hashCode;

  factory DisciplineDetentionAttendance.fromJson(Map<String, dynamic> json) =>
      _$DisciplineDetentionAttendanceFromJson(json);

  Map<String, dynamic> toJson() => _$DisciplineDetentionAttendanceToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum DisciplineDetentionAttendanceAttendanceEnum {
  @JsonValue(r'present')
  present(r'present'),
  @JsonValue(r'absent')
  absent(r'absent'),
  @JsonValue(r'excused')
  excused(r'excused'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const DisciplineDetentionAttendanceAttendanceEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
