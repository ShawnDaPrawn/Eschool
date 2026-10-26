//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'peer_session.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PeerSession {
  /// Returns a new [PeerSession] instance.
  PeerSession({
    required this.id,

    required this.revision,

    required this.teachingGroupId,

    required this.topicId,

    required this.topicName,

    required this.tutorLearnerId,

    required this.tutorName,

    required this.tuteeLearnerId,

    required this.tuteeName,

    required this.supervisorUserId,

    required this.supervisorName,

    required this.startsAt,

    required this.endsAt,

    required this.venue,

    required this.status,

    required this.verifiedMinutes,

    required this.serviceMinutes,

    required this.serviceForReader,

    required this.housePointId,

    required this.canComplete,

    required this.canCancel,

    required this.canReverse,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final String id;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  // minimum: 1
  @JsonKey(name: r'teaching_group_id', required: true, includeIfNull: false)
  final int teachingGroupId;

  // minimum: 1
  @JsonKey(name: r'topic_id', required: true, includeIfNull: false)
  final int topicId;

  @JsonKey(name: r'topic_name', required: true, includeIfNull: false)
  final String topicName;

  // minimum: 1
  @JsonKey(name: r'tutor_learner_id', required: true, includeIfNull: false)
  final int tutorLearnerId;

  @JsonKey(name: r'tutor_name', required: true, includeIfNull: true)
  final String? tutorName;

  // minimum: 1
  @JsonKey(name: r'tutee_learner_id', required: true, includeIfNull: false)
  final int tuteeLearnerId;

  @JsonKey(name: r'tutee_name', required: true, includeIfNull: true)
  final String? tuteeName;

  // minimum: 1
  @JsonKey(name: r'supervisor_user_id', required: true, includeIfNull: false)
  final int supervisorUserId;

  @JsonKey(name: r'supervisor_name', required: true, includeIfNull: false)
  final String supervisorName;

  @JsonKey(name: r'starts_at', required: true, includeIfNull: false)
  final DateTime startsAt;

  @JsonKey(name: r'ends_at', required: true, includeIfNull: false)
  final DateTime endsAt;

  @JsonKey(name: r'venue', required: true, includeIfNull: false)
  final String venue;

  @JsonKey(
    name: r'status',
    required: true,
    includeIfNull: false,
    unknownEnumValue: PeerSessionStatusEnum.unknownDefaultOpenApi,
  )
  final PeerSessionStatusEnum status;

  // minimum: 1
  // maximum: 180
  @JsonKey(name: r'verified_minutes', required: true, includeIfNull: true)
  final int? verifiedMinutes;

  // minimum: 0
  // maximum: 180
  @JsonKey(name: r'service_minutes', required: true, includeIfNull: true)
  final int? serviceMinutes;

  @JsonKey(name: r'service_for_reader', required: true, includeIfNull: false)
  final bool serviceForReader;

  // minimum: 1
  @JsonKey(name: r'house_point_id', required: true, includeIfNull: true)
  final int? housePointId;

  @JsonKey(name: r'can_complete', required: true, includeIfNull: false)
  final bool canComplete;

  @JsonKey(name: r'can_cancel', required: true, includeIfNull: false)
  final bool canCancel;

  @JsonKey(name: r'can_reverse', required: true, includeIfNull: false)
  final bool canReverse;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PeerSession &&
          other.id == id &&
          other.revision == revision &&
          other.teachingGroupId == teachingGroupId &&
          other.topicId == topicId &&
          other.topicName == topicName &&
          other.tutorLearnerId == tutorLearnerId &&
          other.tutorName == tutorName &&
          other.tuteeLearnerId == tuteeLearnerId &&
          other.tuteeName == tuteeName &&
          other.supervisorUserId == supervisorUserId &&
          other.supervisorName == supervisorName &&
          other.startsAt == startsAt &&
          other.endsAt == endsAt &&
          other.venue == venue &&
          other.status == status &&
          other.verifiedMinutes == verifiedMinutes &&
          other.serviceMinutes == serviceMinutes &&
          other.serviceForReader == serviceForReader &&
          other.housePointId == housePointId &&
          other.canComplete == canComplete &&
          other.canCancel == canCancel &&
          other.canReverse == canReverse;

  @override
  int get hashCode =>
      id.hashCode +
      revision.hashCode +
      teachingGroupId.hashCode +
      topicId.hashCode +
      topicName.hashCode +
      tutorLearnerId.hashCode +
      (tutorName == null ? 0 : tutorName.hashCode) +
      tuteeLearnerId.hashCode +
      (tuteeName == null ? 0 : tuteeName.hashCode) +
      supervisorUserId.hashCode +
      supervisorName.hashCode +
      startsAt.hashCode +
      endsAt.hashCode +
      venue.hashCode +
      status.hashCode +
      (verifiedMinutes == null ? 0 : verifiedMinutes.hashCode) +
      (serviceMinutes == null ? 0 : serviceMinutes.hashCode) +
      serviceForReader.hashCode +
      (housePointId == null ? 0 : housePointId.hashCode) +
      canComplete.hashCode +
      canCancel.hashCode +
      canReverse.hashCode;

  factory PeerSession.fromJson(Map<String, dynamic> json) =>
      _$PeerSessionFromJson(json);

  Map<String, dynamic> toJson() => _$PeerSessionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum PeerSessionStatusEnum {
  @JsonValue(r'scheduled')
  scheduled(r'scheduled'),
  @JsonValue(r'completed')
  completed(r'completed'),
  @JsonValue(r'cancelled')
  cancelled(r'cancelled'),
  @JsonValue(r'reversed')
  reversed(r'reversed'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const PeerSessionStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
