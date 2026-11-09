//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'emergency_roll_call_marked.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class EmergencyRollCallMarked {
  /// Returns a new [EmergencyRollCallMarked] instance.
  EmergencyRollCallMarked({
    required this.alertId,

    required this.status,

    required this.learnerIds,

    required this.alreadyReleased,
  });

  @JsonKey(name: r'alert_id', required: true, includeIfNull: false)
  final String alertId;

  @JsonKey(
    name: r'status',
    required: true,
    includeIfNull: false,
    unknownEnumValue: EmergencyRollCallMarkedStatusEnum.unknownDefaultOpenApi,
  )
  final EmergencyRollCallMarkedStatusEnum status;

  @JsonKey(name: r'learner_ids', required: true, includeIfNull: false)
  final List<int> learnerIds;

  @JsonKey(name: r'already_released', required: true, includeIfNull: false)
  final List<int> alreadyReleased;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is EmergencyRollCallMarked &&
          other.alertId == alertId &&
          other.status == status &&
          other.learnerIds == learnerIds &&
          other.alreadyReleased == alreadyReleased;

  @override
  int get hashCode =>
      alertId.hashCode +
      status.hashCode +
      learnerIds.hashCode +
      alreadyReleased.hashCode;

  factory EmergencyRollCallMarked.fromJson(Map<String, dynamic> json) =>
      _$EmergencyRollCallMarkedFromJson(json);

  Map<String, dynamic> toJson() => _$EmergencyRollCallMarkedToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum EmergencyRollCallMarkedStatusEnum {
  @JsonValue(r'accounted')
  accounted(r'accounted'),
  @JsonValue(r'unaccounted')
  unaccounted(r'unaccounted'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const EmergencyRollCallMarkedStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
