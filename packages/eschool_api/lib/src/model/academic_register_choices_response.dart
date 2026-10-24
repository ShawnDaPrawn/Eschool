//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/academic_register_choices_data.dart';
import 'package:json_annotation/json_annotation.dart';

part 'academic_register_choices_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicRegisterChoicesResponse {
  /// Returns a new [AcademicRegisterChoicesResponse] instance.
  AcademicRegisterChoicesResponse({
    required this.error,

    required this.message,

    required this.code,

    required this.data,
  });

  /// Always false.
  @JsonKey(name: r'error', required: true, includeIfNull: false)
  final bool error;

  @JsonKey(name: r'message', required: true, includeIfNull: false)
  final String message;

  /// Always 200.
  @JsonKey(name: r'code', required: true, includeIfNull: false)
  final int code;

  @JsonKey(name: r'data', required: true, includeIfNull: false)
  final AcademicRegisterChoicesData data;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicRegisterChoicesResponse &&
          other.error == error &&
          other.message == message &&
          other.code == code &&
          other.data == data;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + code.hashCode + data.hashCode;

  factory AcademicRegisterChoicesResponse.fromJson(Map<String, dynamic> json) =>
      _$AcademicRegisterChoicesResponseFromJson(json);

  Map<String, dynamic> toJson() =>
      _$AcademicRegisterChoicesResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
