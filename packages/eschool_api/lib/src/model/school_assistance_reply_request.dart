//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'school_assistance_reply_request.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SchoolAssistanceReplyRequest {
  /// Returns a new [SchoolAssistanceReplyRequest] instance.
  SchoolAssistanceReplyRequest({
    required this.operationId,

    required this.text,

    required this.language,

    required this.persona,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @JsonKey(name: r'text', required: true, includeIfNull: false)
  final String text;

  @JsonKey(name: r'language', required: true, includeIfNull: false)
  final String language;

  @JsonKey(
    name: r'persona',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        SchoolAssistanceReplyRequestPersonaEnum.unknownDefaultOpenApi,
  )
  final SchoolAssistanceReplyRequestPersonaEnum persona;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SchoolAssistanceReplyRequest &&
          other.operationId == operationId &&
          other.text == text &&
          other.language == language &&
          other.persona == persona;

  @override
  int get hashCode =>
      operationId.hashCode +
      text.hashCode +
      language.hashCode +
      persona.hashCode;

  factory SchoolAssistanceReplyRequest.fromJson(Map<String, dynamic> json) =>
      _$SchoolAssistanceReplyRequestFromJson(json);

  Map<String, dynamic> toJson() => _$SchoolAssistanceReplyRequestToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum SchoolAssistanceReplyRequestPersonaEnum {
  @JsonValue(r'parent')
  parent(r'parent'),
  @JsonValue(r'office')
  office(r'office'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const SchoolAssistanceReplyRequestPersonaEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
