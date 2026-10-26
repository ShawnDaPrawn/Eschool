//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/school_assistance_turn.dart';
import 'package:json_annotation/json_annotation.dart';

part 'school_assistance_turn_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SchoolAssistanceTurnResponse {
  /// Returns a new [SchoolAssistanceTurnResponse] instance.
  SchoolAssistanceTurnResponse({
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
  final SchoolAssistanceTurn data;

  @JsonKey(
    name: r'code',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        SchoolAssistanceTurnResponseCodeEnum.unknownDefaultOpenApi,
  )
  final SchoolAssistanceTurnResponseCodeEnum code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SchoolAssistanceTurnResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + data.hashCode + code.hashCode;

  factory SchoolAssistanceTurnResponse.fromJson(Map<String, dynamic> json) =>
      _$SchoolAssistanceTurnResponseFromJson(json);

  Map<String, dynamic> toJson() => _$SchoolAssistanceTurnResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum SchoolAssistanceTurnResponseCodeEnum {
  @JsonValue(200)
  number200('200'),
  @JsonValue(11184809)
  unknownDefaultOpenApi('11184809');

  const SchoolAssistanceTurnResponseCodeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
