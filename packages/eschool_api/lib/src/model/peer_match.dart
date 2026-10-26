//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'peer_match.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PeerMatch {
  /// Returns a new [PeerMatch] instance.
  PeerMatch({
    required this.tutorLearnerId,

    required this.tutorName,

    required this.tuteeLearnerId,

    required this.topicId,

    required this.topicName,

    required this.reason,
  });

  // minimum: 1
  @JsonKey(name: r'tutor_learner_id', required: true, includeIfNull: false)
  final int tutorLearnerId;

  @JsonKey(name: r'tutor_name', required: true, includeIfNull: false)
  final String tutorName;

  // minimum: 1
  @JsonKey(name: r'tutee_learner_id', required: true, includeIfNull: false)
  final int tuteeLearnerId;

  // minimum: 1
  @JsonKey(name: r'topic_id', required: true, includeIfNull: false)
  final int topicId;

  @JsonKey(name: r'topic_name', required: true, includeIfNull: false)
  final String topicName;

  @JsonKey(
    name: r'reason',
    required: true,
    includeIfNull: false,
    unknownEnumValue: PeerMatchReasonEnum.unknownDefaultOpenApi,
  )
  final PeerMatchReasonEnum reason;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PeerMatch &&
          other.tutorLearnerId == tutorLearnerId &&
          other.tutorName == tutorName &&
          other.tuteeLearnerId == tuteeLearnerId &&
          other.topicId == topicId &&
          other.topicName == topicName &&
          other.reason == reason;

  @override
  int get hashCode =>
      tutorLearnerId.hashCode +
      tutorName.hashCode +
      tuteeLearnerId.hashCode +
      topicId.hashCode +
      topicName.hashCode +
      reason.hashCode;

  factory PeerMatch.fromJson(Map<String, dynamic> json) =>
      _$PeerMatchFromJson(json);

  Map<String, dynamic> toJson() => _$PeerMatchToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum PeerMatchReasonEnum {
  @JsonValue(r'not_yet_assessed')
  notYetAssessed(r'not_yet_assessed'),
  @JsonValue(r'prerequisite_gap')
  prerequisiteGap(r'prerequisite_gap'),
  @JsonValue(r'topic_gap')
  topicGap(r'topic_gap'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const PeerMatchReasonEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
