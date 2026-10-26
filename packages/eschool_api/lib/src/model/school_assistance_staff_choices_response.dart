//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/school_assistance_staff_choices.dart';
import 'package:json_annotation/json_annotation.dart';

part 'school_assistance_staff_choices_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SchoolAssistanceStaffChoicesResponse {
  /// Returns a new [SchoolAssistanceStaffChoicesResponse] instance.
  SchoolAssistanceStaffChoicesResponse({
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
  final SchoolAssistanceStaffChoices data;

  @JsonKey(
    name: r'code',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        SchoolAssistanceStaffChoicesResponseCodeEnum.unknownDefaultOpenApi,
  )
  final SchoolAssistanceStaffChoicesResponseCodeEnum code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SchoolAssistanceStaffChoicesResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + data.hashCode + code.hashCode;

  factory SchoolAssistanceStaffChoicesResponse.fromJson(
    Map<String, dynamic> json,
  ) => _$SchoolAssistanceStaffChoicesResponseFromJson(json);

  Map<String, dynamic> toJson() =>
      _$SchoolAssistanceStaffChoicesResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum SchoolAssistanceStaffChoicesResponseCodeEnum {
  @JsonValue(200)
  number200('200'),
  @JsonValue(11184809)
  unknownDefaultOpenApi('11184809');

  const SchoolAssistanceStaffChoicesResponseCodeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
