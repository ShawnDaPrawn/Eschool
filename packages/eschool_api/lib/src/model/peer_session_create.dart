//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'peer_session_create.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PeerSessionCreate {
  /// Returns a new [PeerSessionCreate] instance.
  PeerSessionCreate({
    required this.persona,

    required this.operationId,

    required this.teachingGroupId,

    required this.tutorLearnerId,

    required this.tuteeLearnerId,

    required this.topicId,

    required this.venue,

    required this.startsAt,

    required this.endsAt,
  });

  @JsonKey(
    name: r'persona',
    required: true,
    includeIfNull: false,
    unknownEnumValue: PeerSessionCreatePersonaEnum.unknownDefaultOpenApi,
  )
  final PeerSessionCreatePersonaEnum persona;

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'teaching_group_id', required: true, includeIfNull: false)
  final int teachingGroupId;

  // minimum: 1
  @JsonKey(name: r'tutor_learner_id', required: true, includeIfNull: false)
  final int tutorLearnerId;

  // minimum: 1
  @JsonKey(name: r'tutee_learner_id', required: true, includeIfNull: false)
  final int tuteeLearnerId;

  // minimum: 1
  @JsonKey(name: r'topic_id', required: true, includeIfNull: false)
  final int topicId;

  @JsonKey(name: r'venue', required: true, includeIfNull: false)
  final String venue;

  /// Unambiguous school-local wall clock; no guessed DST fold.
  @JsonKey(name: r'starts_at', required: true, includeIfNull: false)
  final String startsAt;

  /// Unambiguous school-local wall clock; no guessed DST fold.
  @JsonKey(name: r'ends_at', required: true, includeIfNull: false)
  final String endsAt;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PeerSessionCreate &&
          other.persona == persona &&
          other.operationId == operationId &&
          other.teachingGroupId == teachingGroupId &&
          other.tutorLearnerId == tutorLearnerId &&
          other.tuteeLearnerId == tuteeLearnerId &&
          other.topicId == topicId &&
          other.venue == venue &&
          other.startsAt == startsAt &&
          other.endsAt == endsAt;

  @override
  int get hashCode =>
      persona.hashCode +
      operationId.hashCode +
      teachingGroupId.hashCode +
      tutorLearnerId.hashCode +
      tuteeLearnerId.hashCode +
      topicId.hashCode +
      venue.hashCode +
      startsAt.hashCode +
      endsAt.hashCode;

  factory PeerSessionCreate.fromJson(Map<String, dynamic> json) =>
      _$PeerSessionCreateFromJson(json);

  Map<String, dynamic> toJson() => _$PeerSessionCreateToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum PeerSessionCreatePersonaEnum {
  @JsonValue(r'staff')
  staff(r'staff'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const PeerSessionCreatePersonaEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
