//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/family_visit_configuration.dart';
import 'package:json_annotation/json_annotation.dart';

part 'family_visit_configuration_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FamilyVisitConfigurationResponse {
  /// Returns a new [FamilyVisitConfigurationResponse] instance.
  FamilyVisitConfigurationResponse({
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
  final FamilyVisitConfiguration data;

  @JsonKey(
    name: r'code',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        FamilyVisitConfigurationResponseCodeEnum.unknownDefaultOpenApi,
  )
  final FamilyVisitConfigurationResponseCodeEnum code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FamilyVisitConfigurationResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + data.hashCode + code.hashCode;

  factory FamilyVisitConfigurationResponse.fromJson(
    Map<String, dynamic> json,
  ) => _$FamilyVisitConfigurationResponseFromJson(json);

  Map<String, dynamic> toJson() =>
      _$FamilyVisitConfigurationResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum FamilyVisitConfigurationResponseCodeEnum {
  @JsonValue(200)
  number200('200'),
  @JsonValue(11184809)
  unknownDefaultOpenApi('11184809');

  const FamilyVisitConfigurationResponseCodeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
