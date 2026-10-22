//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/validation_errors.dart';
import 'package:eschool_api/src/model/error_code.dart';
import 'package:json_annotation/json_annotation.dart';

part 'error_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ErrorResponse {
  /// Returns a new [ErrorResponse] instance.
  ErrorResponse({
    required this.error,

    required this.message,

    required this.data,

    required this.code,

    this.details,
  });

  /// Always true.
  @JsonKey(name: r'error', required: true, includeIfNull: false)
  final bool error;

  /// A sentence the app may show (for validation, the first message).
  @JsonKey(name: r'message', required: true, includeIfNull: false)
  final String message;

  /// Every validation message by field, for `validation_failed`; otherwise null.
  @JsonKey(name: r'data', required: true, includeIfNull: true)
  final ValidationErrors? data;

  @JsonKey(
    name: r'code',
    required: true,
    includeIfNull: false,
    unknownEnumValue: ErrorCode.unknownDefaultOpenApi,
  )
  final ErrorCode code;

  /// Exception text, only when the server runs in debug mode.
  @JsonKey(name: r'details', required: false, includeIfNull: false)
  final String? details;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ErrorResponse &&
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

  factory ErrorResponse.fromJson(Map<String, dynamic> json) =>
      _$ErrorResponseFromJson(json);

  Map<String, dynamic> toJson() => _$ErrorResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
