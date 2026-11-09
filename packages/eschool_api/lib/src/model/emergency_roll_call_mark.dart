//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'emergency_roll_call_mark.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class EmergencyRollCallMark {
  /// Returns a new [EmergencyRollCallMark] instance.
  EmergencyRollCallMark({
    required this.operationId,

    required this.learnerIds,

    required this.status,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @JsonKey(name: r'learner_ids', required: true, includeIfNull: false)
  final List<int> learnerIds;

  @JsonKey(
    name: r'status',
    required: true,
    includeIfNull: false,
    unknownEnumValue: EmergencyRollCallMarkStatusEnum.unknownDefaultOpenApi,
  )
  final EmergencyRollCallMarkStatusEnum status;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is EmergencyRollCallMark &&
          other.operationId == operationId &&
          other.learnerIds == learnerIds &&
          other.status == status;

  @override
  int get hashCode =>
      operationId.hashCode + learnerIds.hashCode + status.hashCode;

  factory EmergencyRollCallMark.fromJson(Map<String, dynamic> json) =>
      _$EmergencyRollCallMarkFromJson(json);

  Map<String, dynamic> toJson() => _$EmergencyRollCallMarkToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum EmergencyRollCallMarkStatusEnum {
  @JsonValue(r'accounted')
  accounted(r'accounted'),
  @JsonValue(r'unaccounted')
  unaccounted(r'unaccounted'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const EmergencyRollCallMarkStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
