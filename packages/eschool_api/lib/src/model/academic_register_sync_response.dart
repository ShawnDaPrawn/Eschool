//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/academic_register_sync_data.dart';
import 'package:json_annotation/json_annotation.dart';

part 'academic_register_sync_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicRegisterSyncResponse {
  /// Returns a new [AcademicRegisterSyncResponse] instance.
  AcademicRegisterSyncResponse({
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
  final AcademicRegisterSyncData data;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicRegisterSyncResponse &&
          other.error == error &&
          other.message == message &&
          other.code == code &&
          other.data == data;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + code.hashCode + data.hashCode;

  factory AcademicRegisterSyncResponse.fromJson(Map<String, dynamic> json) =>
      _$AcademicRegisterSyncResponseFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicRegisterSyncResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
