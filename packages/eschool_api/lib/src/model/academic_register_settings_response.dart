//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/academic_register_settings_data.dart';
import 'package:json_annotation/json_annotation.dart';

part 'academic_register_settings_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicRegisterSettingsResponse {
  /// Returns a new [AcademicRegisterSettingsResponse] instance.
  AcademicRegisterSettingsResponse({
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
  final AcademicRegisterSettingsData data;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicRegisterSettingsResponse &&
          other.error == error &&
          other.message == message &&
          other.code == code &&
          other.data == data;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + code.hashCode + data.hashCode;

  factory AcademicRegisterSettingsResponse.fromJson(
    Map<String, dynamic> json,
  ) => _$AcademicRegisterSettingsResponseFromJson(json);

  Map<String, dynamic> toJson() =>
      _$AcademicRegisterSettingsResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
