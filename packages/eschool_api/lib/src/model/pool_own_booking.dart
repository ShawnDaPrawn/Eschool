//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'pool_own_booking.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PoolOwnBooking {
  /// Returns a new [PoolOwnBooking] instance.
  PoolOwnBooking({
    required this.id,

    required this.kind,

    required this.status,

    required this.revision,

    required this.activitySessionId,

    required this.activityGroupId,

    required this.groupName,

    required this.sessionName,

    required this.venue,

    required this.onDate,

    required this.startsAt,

    required this.endsAt,

    required this.attendanceOpen,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(
    name: r'kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue: PoolOwnBookingKindEnum.unknownDefaultOpenApi,
  )
  final PoolOwnBookingKindEnum kind;

  @JsonKey(
    name: r'status',
    required: true,
    includeIfNull: false,
    unknownEnumValue: PoolOwnBookingStatusEnum.unknownDefaultOpenApi,
  )
  final PoolOwnBookingStatusEnum status;

  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'activity_session_id', required: true, includeIfNull: false)
  final int activitySessionId;

  @JsonKey(name: r'activity_group_id', required: true, includeIfNull: false)
  final int activityGroupId;

  @JsonKey(name: r'group_name', required: true, includeIfNull: false)
  final String groupName;

  @JsonKey(name: r'session_name', required: true, includeIfNull: false)
  final String sessionName;

  @JsonKey(name: r'venue', required: true, includeIfNull: false)
  final String venue;

  @JsonKey(name: r'on_date', required: true, includeIfNull: true)
  final String? onDate;

  @JsonKey(name: r'starts_at', required: true, includeIfNull: true)
  final String? startsAt;

  @JsonKey(name: r'ends_at', required: true, includeIfNull: true)
  final String? endsAt;

  @JsonKey(name: r'attendance_open', required: true, includeIfNull: false)
  final bool attendanceOpen;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PoolOwnBooking &&
          other.id == id &&
          other.kind == kind &&
          other.status == status &&
          other.revision == revision &&
          other.activitySessionId == activitySessionId &&
          other.activityGroupId == activityGroupId &&
          other.groupName == groupName &&
          other.sessionName == sessionName &&
          other.venue == venue &&
          other.onDate == onDate &&
          other.startsAt == startsAt &&
          other.endsAt == endsAt &&
          other.attendanceOpen == attendanceOpen;

  @override
  int get hashCode =>
      id.hashCode +
      kind.hashCode +
      status.hashCode +
      revision.hashCode +
      activitySessionId.hashCode +
      activityGroupId.hashCode +
      groupName.hashCode +
      sessionName.hashCode +
      venue.hashCode +
      (onDate == null ? 0 : onDate.hashCode) +
      (startsAt == null ? 0 : startsAt.hashCode) +
      (endsAt == null ? 0 : endsAt.hashCode) +
      attendanceOpen.hashCode;

  factory PoolOwnBooking.fromJson(Map<String, dynamic> json) =>
      _$PoolOwnBookingFromJson(json);

  Map<String, dynamic> toJson() => _$PoolOwnBookingToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum PoolOwnBookingKindEnum {
  @JsonValue(r'career_talk')
  careerTalk(r'career_talk'),
  @JsonValue(r'job_shadow')
  jobShadow(r'job_shadow'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const PoolOwnBookingKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum PoolOwnBookingStatusEnum {
  @JsonValue(r'booked')
  booked(r'booked'),
  @JsonValue(r'cancelled')
  cancelled(r'cancelled'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const PoolOwnBookingStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
