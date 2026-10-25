//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'discipline_detention_attendance_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class DisciplineDetentionAttendanceWrite {
  /// Returns a new [DisciplineDetentionAttendanceWrite] instance.
  DisciplineDetentionAttendanceWrite({
    required this.operationId,

    required this.expectedRevision,

    required this.expectedSessionRevision,

    required this.attendance,

    required this.reason,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'expected_revision', required: true, includeIfNull: false)
  final int expectedRevision;

  // minimum: 1
  @JsonKey(
    name: r'expected_session_revision',
    required: true,
    includeIfNull: false,
  )
  final int expectedSessionRevision;

  @JsonKey(
    name: r'attendance',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        DisciplineDetentionAttendanceWriteAttendanceEnum.unknownDefaultOpenApi,
  )
  final DisciplineDetentionAttendanceWriteAttendanceEnum attendance;

  @JsonKey(name: r'reason', required: true, includeIfNull: false)
  final String reason;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is DisciplineDetentionAttendanceWrite &&
          other.operationId == operationId &&
          other.expectedRevision == expectedRevision &&
          other.expectedSessionRevision == expectedSessionRevision &&
          other.attendance == attendance &&
          other.reason == reason;

  @override
  int get hashCode =>
      operationId.hashCode +
      expectedRevision.hashCode +
      expectedSessionRevision.hashCode +
      attendance.hashCode +
      reason.hashCode;

  factory DisciplineDetentionAttendanceWrite.fromJson(
    Map<String, dynamic> json,
  ) => _$DisciplineDetentionAttendanceWriteFromJson(json);

  Map<String, dynamic> toJson() =>
      _$DisciplineDetentionAttendanceWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum DisciplineDetentionAttendanceWriteAttendanceEnum {
  @JsonValue(r'present')
  present(r'present'),
  @JsonValue(r'absent')
  absent(r'absent'),
  @JsonValue(r'excused')
  excused(r'excused'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const DisciplineDetentionAttendanceWriteAttendanceEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
