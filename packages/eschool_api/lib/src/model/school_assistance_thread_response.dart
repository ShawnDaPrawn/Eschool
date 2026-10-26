//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/school_assistance_thread.dart';
import 'package:json_annotation/json_annotation.dart';

part 'school_assistance_thread_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SchoolAssistanceThreadResponse {
  /// Returns a new [SchoolAssistanceThreadResponse] instance.
  SchoolAssistanceThreadResponse({
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
  final SchoolAssistanceThread data;

  @JsonKey(
    name: r'code',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        SchoolAssistanceThreadResponseCodeEnum.unknownDefaultOpenApi,
  )
  final SchoolAssistanceThreadResponseCodeEnum code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SchoolAssistanceThreadResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + data.hashCode + code.hashCode;

  factory SchoolAssistanceThreadResponse.fromJson(Map<String, dynamic> json) =>
      _$SchoolAssistanceThreadResponseFromJson(json);

  Map<String, dynamic> toJson() => _$SchoolAssistanceThreadResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum SchoolAssistanceThreadResponseCodeEnum {
  @JsonValue(200)
  number200('200'),
  @JsonValue(11184809)
  unknownDefaultOpenApi('11184809');

  const SchoolAssistanceThreadResponseCodeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
