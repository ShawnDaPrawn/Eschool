//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'discipline_family_detention.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class DisciplineFamilyDetention {
  /// Returns a new [DisciplineFamilyDetention] instance.
  DisciplineFamilyDetention({
    required this.sessionId,

    required this.onDate,

    required this.startsAt,

    required this.endsAt,

    required this.timezone,

    required this.location,

    required this.attendance,

    required this.active,
  });

  // minimum: 1
  @JsonKey(name: r'session_id', required: true, includeIfNull: false)
  final int sessionId;

  @JsonKey(name: r'on_date', required: true, includeIfNull: false)
  final String onDate;

  @JsonKey(name: r'starts_at', required: true, includeIfNull: false)
  final String startsAt;

  @JsonKey(name: r'ends_at', required: true, includeIfNull: false)
  final String endsAt;

  @JsonKey(name: r'timezone', required: true, includeIfNull: false)
  final String timezone;

  @JsonKey(name: r'location', required: true, includeIfNull: false)
  final String location;

  @JsonKey(
    name: r'attendance',
    required: true,
    includeIfNull: true,
    unknownEnumValue:
        DisciplineFamilyDetentionAttendanceEnum.unknownDefaultOpenApi,
  )
  final DisciplineFamilyDetentionAttendanceEnum? attendance;

  @JsonKey(name: r'active', required: true, includeIfNull: false)
  final bool active;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is DisciplineFamilyDetention &&
          other.sessionId == sessionId &&
          other.onDate == onDate &&
          other.startsAt == startsAt &&
          other.endsAt == endsAt &&
          other.timezone == timezone &&
          other.location == location &&
          other.attendance == attendance &&
          other.active == active;

  @override
  int get hashCode =>
      sessionId.hashCode +
      onDate.hashCode +
      startsAt.hashCode +
      endsAt.hashCode +
      timezone.hashCode +
      location.hashCode +
      (attendance == null ? 0 : attendance.hashCode) +
      active.hashCode;

  factory DisciplineFamilyDetention.fromJson(Map<String, dynamic> json) =>
      _$DisciplineFamilyDetentionFromJson(json);

  Map<String, dynamic> toJson() => _$DisciplineFamilyDetentionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum DisciplineFamilyDetentionAttendanceEnum {
  @JsonValue(r'present')
  present(r'present'),
  @JsonValue(r'absent')
  absent(r'absent'),
  @JsonValue(r'excused')
  excused(r'excused'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const DisciplineFamilyDetentionAttendanceEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
