//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'emergency_alert_prepare.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class EmergencyAlertPrepare {
  /// Returns a new [EmergencyAlertPrepare] instance.
  EmergencyAlertPrepare({
    required this.operationId,

    required this.kind,

    required this.audience,

    required this.drill,

    this.drillId,

    this.title,

    this.message,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @JsonKey(
    name: r'kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue: EmergencyAlertPrepareKindEnum.unknownDefaultOpenApi,
  )
  final EmergencyAlertPrepareKindEnum kind;

  @JsonKey(
    name: r'audience',
    required: true,
    includeIfNull: false,
    unknownEnumValue: EmergencyAlertPrepareAudienceEnum.unknownDefaultOpenApi,
  )
  final EmergencyAlertPrepareAudienceEnum audience;

  @JsonKey(name: r'drill', required: true, includeIfNull: false)
  final bool drill;

  @JsonKey(name: r'drill_id', required: false, includeIfNull: false)
  final String? drillId;

  @JsonKey(name: r'title', required: false, includeIfNull: false)
  final String? title;

  /// Required for a general emergency alert (kind critical).
  @JsonKey(name: r'message', required: false, includeIfNull: false)
  final String? message;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is EmergencyAlertPrepare &&
          other.operationId == operationId &&
          other.kind == kind &&
          other.audience == audience &&
          other.drill == drill &&
          other.drillId == drillId &&
          other.title == title &&
          other.message == message;

  @override
  int get hashCode =>
      operationId.hashCode +
      kind.hashCode +
      audience.hashCode +
      drill.hashCode +
      (drillId == null ? 0 : drillId.hashCode) +
      (title == null ? 0 : title.hashCode) +
      (message == null ? 0 : message.hashCode);

  factory EmergencyAlertPrepare.fromJson(Map<String, dynamic> json) =>
      _$EmergencyAlertPrepareFromJson(json);

  Map<String, dynamic> toJson() => _$EmergencyAlertPrepareToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum EmergencyAlertPrepareKindEnum {
  @JsonValue(r'lockdown')
  lockdown(r'lockdown'),
  @JsonValue(r'evacuation')
  evacuation(r'evacuation'),
  @JsonValue(r'shelter')
  shelter(r'shelter'),
  @JsonValue(r'critical')
  critical(r'critical'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const EmergencyAlertPrepareKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum EmergencyAlertPrepareAudienceEnum {
  @JsonValue(r'everyone')
  everyone(r'everyone'),
  @JsonValue(r'staff')
  staff(r'staff'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const EmergencyAlertPrepareAudienceEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
