//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/school_assistance_document_body.dart';
import 'package:json_annotation/json_annotation.dart';

part 'school_assistance_document_body_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SchoolAssistanceDocumentBodyResponse {
  /// Returns a new [SchoolAssistanceDocumentBodyResponse] instance.
  SchoolAssistanceDocumentBodyResponse({
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
  final SchoolAssistanceDocumentBody data;

  @JsonKey(
    name: r'code',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        SchoolAssistanceDocumentBodyResponseCodeEnum.unknownDefaultOpenApi,
  )
  final SchoolAssistanceDocumentBodyResponseCodeEnum code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SchoolAssistanceDocumentBodyResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + data.hashCode + code.hashCode;

  factory SchoolAssistanceDocumentBodyResponse.fromJson(
    Map<String, dynamic> json,
  ) => _$SchoolAssistanceDocumentBodyResponseFromJson(json);

  Map<String, dynamic> toJson() =>
      _$SchoolAssistanceDocumentBodyResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum SchoolAssistanceDocumentBodyResponseCodeEnum {
  @JsonValue(200)
  number200('200'),
  @JsonValue(11184809)
  unknownDefaultOpenApi('11184809');

  const SchoolAssistanceDocumentBodyResponseCodeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
