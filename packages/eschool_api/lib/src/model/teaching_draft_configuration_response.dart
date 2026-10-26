//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/teaching_draft_configuration.dart';
import 'package:json_annotation/json_annotation.dart';

part 'teaching_draft_configuration_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TeachingDraftConfigurationResponse {
  /// Returns a new [TeachingDraftConfigurationResponse] instance.
  TeachingDraftConfigurationResponse({
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
  final TeachingDraftConfiguration data;

  @JsonKey(
    name: r'code',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        TeachingDraftConfigurationResponseCodeEnum.unknownDefaultOpenApi,
  )
  final TeachingDraftConfigurationResponseCodeEnum code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TeachingDraftConfigurationResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + data.hashCode + code.hashCode;

  factory TeachingDraftConfigurationResponse.fromJson(
    Map<String, dynamic> json,
  ) => _$TeachingDraftConfigurationResponseFromJson(json);

  Map<String, dynamic> toJson() =>
      _$TeachingDraftConfigurationResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum TeachingDraftConfigurationResponseCodeEnum {
  @JsonValue(200)
  number200('200'),
  @JsonValue(11184809)
  unknownDefaultOpenApi('11184809');

  const TeachingDraftConfigurationResponseCodeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
