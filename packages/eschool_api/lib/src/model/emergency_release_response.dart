//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/emergency_release_record.dart';
import 'package:json_annotation/json_annotation.dart';

part 'emergency_release_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class EmergencyReleaseResponse {
  /// Returns a new [EmergencyReleaseResponse] instance.
  EmergencyReleaseResponse({
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
  final EmergencyReleaseRecord data;

  @JsonKey(
    name: r'code',
    required: true,
    includeIfNull: false,
    unknownEnumValue: EmergencyReleaseResponseCodeEnum.unknownDefaultOpenApi,
  )
  final EmergencyReleaseResponseCodeEnum code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is EmergencyReleaseResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + data.hashCode + code.hashCode;

  factory EmergencyReleaseResponse.fromJson(Map<String, dynamic> json) =>
      _$EmergencyReleaseResponseFromJson(json);

  Map<String, dynamic> toJson() => _$EmergencyReleaseResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum EmergencyReleaseResponseCodeEnum {
  @JsonValue(200)
  number200('200'),
  @JsonValue(11184809)
  unknownDefaultOpenApi('11184809');

  const EmergencyReleaseResponseCodeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
