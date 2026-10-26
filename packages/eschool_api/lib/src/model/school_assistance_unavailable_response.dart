//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/error_code.dart';
import 'package:eschool_api/src/model/school_assistance_unavailable_response_data.dart';
import 'package:json_annotation/json_annotation.dart';

part 'school_assistance_unavailable_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SchoolAssistanceUnavailableResponse {
  /// Returns a new [SchoolAssistanceUnavailableResponse] instance.
  SchoolAssistanceUnavailableResponse({
    required this.error,

    required this.message,

    required this.data,

    required this.code,

    this.details,
  });

  @JsonKey(name: r'error', required: true, includeIfNull: false)
  final bool error;

  @JsonKey(name: r'message', required: true, includeIfNull: false)
  final String message;

  @JsonKey(name: r'data', required: true, includeIfNull: true)
  final SchoolAssistanceUnavailableResponseData? data;

  @JsonKey(
    name: r'code',
    required: true,
    includeIfNull: false,
    unknownEnumValue: ErrorCode.unknownDefaultOpenApi,
  )
  final ErrorCode code;

  @JsonKey(name: r'details', required: false, includeIfNull: false)
  final String? details;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SchoolAssistanceUnavailableResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code &&
          other.details == details;

  @override
  int get hashCode =>
      error.hashCode +
      message.hashCode +
      (data == null ? 0 : data.hashCode) +
      code.hashCode +
      details.hashCode;

  factory SchoolAssistanceUnavailableResponse.fromJson(
    Map<String, dynamic> json,
  ) => _$SchoolAssistanceUnavailableResponseFromJson(json);

  Map<String, dynamic> toJson() =>
      _$SchoolAssistanceUnavailableResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
