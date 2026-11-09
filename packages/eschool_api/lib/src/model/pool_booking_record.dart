//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'pool_booking_record.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PoolBookingRecord {
  /// Returns a new [PoolBookingRecord] instance.
  PoolBookingRecord({
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

    required this.poolMemberId,

    required this.poolMemberName,

    required this.cancelReason,

    required this.vettedForSession,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(
    name: r'kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue: PoolBookingRecordKindEnum.unknownDefaultOpenApi,
  )
  final PoolBookingRecordKindEnum kind;

  @JsonKey(
    name: r'status',
    required: true,
    includeIfNull: false,
    unknownEnumValue: PoolBookingRecordStatusEnum.unknownDefaultOpenApi,
  )
  final PoolBookingRecordStatusEnum status;

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

  @JsonKey(name: r'pool_member_id', required: true, includeIfNull: false)
  final int poolMemberId;

  @JsonKey(name: r'pool_member_name', required: true, includeIfNull: false)
  final String poolMemberName;

  @JsonKey(name: r'cancel_reason', required: true, includeIfNull: true)
  final String? cancelReason;

  @JsonKey(name: r'vetted_for_session', required: true, includeIfNull: false)
  final bool vettedForSession;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PoolBookingRecord &&
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
          other.poolMemberId == poolMemberId &&
          other.poolMemberName == poolMemberName &&
          other.cancelReason == cancelReason &&
          other.vettedForSession == vettedForSession;

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
      poolMemberId.hashCode +
      poolMemberName.hashCode +
      (cancelReason == null ? 0 : cancelReason.hashCode) +
      vettedForSession.hashCode;

  factory PoolBookingRecord.fromJson(Map<String, dynamic> json) =>
      _$PoolBookingRecordFromJson(json);

  Map<String, dynamic> toJson() => _$PoolBookingRecordToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum PoolBookingRecordKindEnum {
  @JsonValue(r'career_talk')
  careerTalk(r'career_talk'),
  @JsonValue(r'job_shadow')
  jobShadow(r'job_shadow'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const PoolBookingRecordKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum PoolBookingRecordStatusEnum {
  @JsonValue(r'booked')
  booked(r'booked'),
  @JsonValue(r'cancelled')
  cancelled(r'cancelled'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const PoolBookingRecordStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
