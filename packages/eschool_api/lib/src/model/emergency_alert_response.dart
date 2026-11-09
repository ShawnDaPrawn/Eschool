//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/emergency_alert.dart';
import 'package:json_annotation/json_annotation.dart';

part 'emergency_alert_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class EmergencyAlertResponse {
  /// Returns a new [EmergencyAlertResponse] instance.
  EmergencyAlertResponse({
    required this.error,

    required this.message,

    required this.data,

    required this.code,
  });

  @JsonKey(name: r'error', required: true, includeIfNull: false)
  final bool error;

  @JsonKey(name: r'message', required: true, includeIfNull: false)
  final String message;

  @JsonKey(name: r'data', required: true, includeIfNull: false)
  final EmergencyAlert data;

  @JsonKey(
    name: r'code',
    required: true,
    includeIfNull: false,
    unknownEnumValue: EmergencyAlertResponseCodeEnum.unknownDefaultOpenApi,
  )
  final EmergencyAlertResponseCodeEnum code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is EmergencyAlertResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + data.hashCode + code.hashCode;

  factory EmergencyAlertResponse.fromJson(Map<String, dynamic> json) =>
      _$EmergencyAlertResponseFromJson(json);

  Map<String, dynamic> toJson() => _$EmergencyAlertResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum EmergencyAlertResponseCodeEnum {
  @JsonValue(200)
  number200('200'),
  @JsonValue(11184809)
  unknownDefaultOpenApi('11184809');

  const EmergencyAlertResponseCodeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
