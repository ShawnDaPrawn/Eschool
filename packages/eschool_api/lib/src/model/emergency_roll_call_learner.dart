//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'emergency_roll_call_learner.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class EmergencyRollCallLearner {
  /// Returns a new [EmergencyRollCallLearner] instance.
  EmergencyRollCallLearner({
    required this.learnerId,

    required this.name,

    required this.status,

    required this.markedAt,

    required this.releasedAt,

    required this.releasedTo,
  });

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(
    name: r'status',
    required: true,
    includeIfNull: false,
    unknownEnumValue: EmergencyRollCallLearnerStatusEnum.unknownDefaultOpenApi,
  )
  final EmergencyRollCallLearnerStatusEnum status;

  @JsonKey(name: r'marked_at', required: true, includeIfNull: true)
  final DateTime? markedAt;

  @JsonKey(name: r'released_at', required: true, includeIfNull: true)
  final DateTime? releasedAt;

  @JsonKey(
    name: r'released_to',
    required: true,
    includeIfNull: true,
    unknownEnumValue:
        EmergencyRollCallLearnerReleasedToEnum.unknownDefaultOpenApi,
  )
  final EmergencyRollCallLearnerReleasedToEnum? releasedTo;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is EmergencyRollCallLearner &&
          other.learnerId == learnerId &&
          other.name == name &&
          other.status == status &&
          other.markedAt == markedAt &&
          other.releasedAt == releasedAt &&
          other.releasedTo == releasedTo;

  @override
  int get hashCode =>
      learnerId.hashCode +
      name.hashCode +
      status.hashCode +
      (markedAt == null ? 0 : markedAt.hashCode) +
      (releasedAt == null ? 0 : releasedAt.hashCode) +
      (releasedTo == null ? 0 : releasedTo.hashCode);

  factory EmergencyRollCallLearner.fromJson(Map<String, dynamic> json) =>
      _$EmergencyRollCallLearnerFromJson(json);

  Map<String, dynamic> toJson() => _$EmergencyRollCallLearnerToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum EmergencyRollCallLearnerStatusEnum {
  @JsonValue(r'accounted')
  accounted(r'accounted'),
  @JsonValue(r'unaccounted')
  unaccounted(r'unaccounted'),
  @JsonValue(r'released')
  released(r'released'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const EmergencyRollCallLearnerStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum EmergencyRollCallLearnerReleasedToEnum {
  @JsonValue(r'guardian')
  guardian(r'guardian'),
  @JsonValue(r'collector')
  collector(r'collector'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const EmergencyRollCallLearnerReleasedToEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
