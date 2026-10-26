//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/school_assistance_thread_page.dart';
import 'package:json_annotation/json_annotation.dart';

part 'school_assistance_thread_page_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SchoolAssistanceThreadPageResponse {
  /// Returns a new [SchoolAssistanceThreadPageResponse] instance.
  SchoolAssistanceThreadPageResponse({
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
  final SchoolAssistanceThreadPage data;

  @JsonKey(
    name: r'code',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        SchoolAssistanceThreadPageResponseCodeEnum.unknownDefaultOpenApi,
  )
  final SchoolAssistanceThreadPageResponseCodeEnum code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SchoolAssistanceThreadPageResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + data.hashCode + code.hashCode;

  factory SchoolAssistanceThreadPageResponse.fromJson(
    Map<String, dynamic> json,
  ) => _$SchoolAssistanceThreadPageResponseFromJson(json);

  Map<String, dynamic> toJson() =>
      _$SchoolAssistanceThreadPageResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum SchoolAssistanceThreadPageResponseCodeEnum {
  @JsonValue(200)
  number200('200'),
  @JsonValue(11184809)
  unknownDefaultOpenApi('11184809');

  const SchoolAssistanceThreadPageResponseCodeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
