//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'emergency_public_alert.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class EmergencyPublicAlert {
  /// Returns a new [EmergencyPublicAlert] instance.
  EmergencyPublicAlert({
    required this.id,

    required this.kind,

    required this.drill,

    required this.status,

    required this.title,

    required this.message,

    required this.activatedAt,

    required this.allClearAt,

    required this.allClearMessage,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final String id;

  @JsonKey(
    name: r'kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue: EmergencyPublicAlertKindEnum.unknownDefaultOpenApi,
  )
  final EmergencyPublicAlertKindEnum kind;

  @JsonKey(name: r'drill', required: true, includeIfNull: false)
  final bool drill;

  @JsonKey(
    name: r'status',
    required: true,
    includeIfNull: false,
    unknownEnumValue: EmergencyPublicAlertStatusEnum.unknownDefaultOpenApi,
  )
  final EmergencyPublicAlertStatusEnum status;

  /// Starts with \"DRILL:\" for a drill.
  @JsonKey(name: r'title', required: true, includeIfNull: false)
  final String title;

  /// Starts with \"This is a drill. There is no emergency.\" for a drill.
  @JsonKey(name: r'message', required: true, includeIfNull: false)
  final String message;

  @JsonKey(name: r'activated_at', required: true, includeIfNull: true)
  final DateTime? activatedAt;

  @JsonKey(name: r'all_clear_at', required: true, includeIfNull: true)
  final DateTime? allClearAt;

  @JsonKey(name: r'all_clear_message', required: true, includeIfNull: true)
  final String? allClearMessage;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is EmergencyPublicAlert &&
          other.id == id &&
          other.kind == kind &&
          other.drill == drill &&
          other.status == status &&
          other.title == title &&
          other.message == message &&
          other.activatedAt == activatedAt &&
          other.allClearAt == allClearAt &&
          other.allClearMessage == allClearMessage;

  @override
  int get hashCode =>
      id.hashCode +
      kind.hashCode +
      drill.hashCode +
      status.hashCode +
      title.hashCode +
      message.hashCode +
      (activatedAt == null ? 0 : activatedAt.hashCode) +
      (allClearAt == null ? 0 : allClearAt.hashCode) +
      (allClearMessage == null ? 0 : allClearMessage.hashCode);

  factory EmergencyPublicAlert.fromJson(Map<String, dynamic> json) =>
      _$EmergencyPublicAlertFromJson(json);

  Map<String, dynamic> toJson() => _$EmergencyPublicAlertToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum EmergencyPublicAlertKindEnum {
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

  const EmergencyPublicAlertKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum EmergencyPublicAlertStatusEnum {
  @JsonValue(r'active')
  active(r'active'),
  @JsonValue(r'all_clear')
  allClear(r'all_clear'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const EmergencyPublicAlertStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
