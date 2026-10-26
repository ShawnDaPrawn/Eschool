//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'school_assistance_close_request.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SchoolAssistanceCloseRequest {
  /// Returns a new [SchoolAssistanceCloseRequest] instance.
  SchoolAssistanceCloseRequest({
    required this.operationId,

    required this.expectedRevision,

    required this.persona,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'expected_revision', required: true, includeIfNull: false)
  final int expectedRevision;

  @JsonKey(
    name: r'persona',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        SchoolAssistanceCloseRequestPersonaEnum.unknownDefaultOpenApi,
  )
  final SchoolAssistanceCloseRequestPersonaEnum persona;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SchoolAssistanceCloseRequest &&
          other.operationId == operationId &&
          other.expectedRevision == expectedRevision &&
          other.persona == persona;

  @override
  int get hashCode =>
      operationId.hashCode + expectedRevision.hashCode + persona.hashCode;

  factory SchoolAssistanceCloseRequest.fromJson(Map<String, dynamic> json) =>
      _$SchoolAssistanceCloseRequestFromJson(json);

  Map<String, dynamic> toJson() => _$SchoolAssistanceCloseRequestToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum SchoolAssistanceCloseRequestPersonaEnum {
  @JsonValue(r'parent')
  parent(r'parent'),
  @JsonValue(r'office')
  office(r'office'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const SchoolAssistanceCloseRequestPersonaEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
