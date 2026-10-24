//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/academic_register_qr_data.dart';
import 'package:json_annotation/json_annotation.dart';

part 'academic_register_qr_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicRegisterQrResponse {
  /// Returns a new [AcademicRegisterQrResponse] instance.
  AcademicRegisterQrResponse({
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
  final AcademicRegisterQrData data;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicRegisterQrResponse &&
          other.error == error &&
          other.message == message &&
          other.code == code &&
          other.data == data;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + code.hashCode + data.hashCode;

  factory AcademicRegisterQrResponse.fromJson(Map<String, dynamic> json) =>
      _$AcademicRegisterQrResponseFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicRegisterQrResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
