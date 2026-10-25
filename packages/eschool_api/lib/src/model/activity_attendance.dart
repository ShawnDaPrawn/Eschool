//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'activity_attendance.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ActivityAttendance {
  /// Returns a new [ActivityAttendance] instance.
  ActivityAttendance({
    required this.id,

    required this.activitySessionId,

    required this.learnerId,

    required this.status,

    required this.recordedBy,

    required this.reason,

    required this.revision,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'activity_session_id', required: true, includeIfNull: false)
  final int activitySessionId;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(
    name: r'status',
    required: true,
    includeIfNull: false,
    unknownEnumValue: ActivityAttendanceStatusEnum.unknownDefaultOpenApi,
  )
  final ActivityAttendanceStatusEnum status;

  // minimum: 1
  @JsonKey(name: r'recorded_by', required: true, includeIfNull: false)
  final int recordedBy;

  @JsonKey(name: r'reason', required: true, includeIfNull: false)
  final String reason;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ActivityAttendance &&
          other.id == id &&
          other.activitySessionId == activitySessionId &&
          other.learnerId == learnerId &&
          other.status == status &&
          other.recordedBy == recordedBy &&
          other.reason == reason &&
          other.revision == revision;

  @override
  int get hashCode =>
      id.hashCode +
      activitySessionId.hashCode +
      learnerId.hashCode +
      status.hashCode +
      recordedBy.hashCode +
      reason.hashCode +
      revision.hashCode;

  factory ActivityAttendance.fromJson(Map<String, dynamic> json) =>
      _$ActivityAttendanceFromJson(json);

  Map<String, dynamic> toJson() => _$ActivityAttendanceToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum ActivityAttendanceStatusEnum {
  @JsonValue(r'present')
  present(r'present'),
  @JsonValue(r'absent')
  absent(r'absent'),
  @JsonValue(r'late')
  late_(r'late'),
  @JsonValue(r'excused')
  excused(r'excused'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const ActivityAttendanceStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
