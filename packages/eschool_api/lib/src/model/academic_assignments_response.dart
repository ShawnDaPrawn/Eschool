//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/academic_assignments_response_data.dart';
import 'package:json_annotation/json_annotation.dart';

part 'academic_assignments_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicAssignmentsResponse {
  /// Returns a new [AcademicAssignmentsResponse] instance.
  AcademicAssignmentsResponse({
    required this.error,

    required this.message,

    required this.code,

    required this.data,
  });

  @JsonKey(name: r'error', required: true, includeIfNull: false)
  final bool error;

  @JsonKey(name: r'message', required: true, includeIfNull: false)
  final String message;

  @JsonKey(name: r'code', required: true, includeIfNull: false)
  final int code;

  @JsonKey(name: r'data', required: true, includeIfNull: false)
  final AcademicAssignmentsResponseData data;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicAssignmentsResponse &&
          other.error == error &&
          other.message == message &&
          other.code == code &&
          other.data == data;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + code.hashCode + data.hashCode;

  factory AcademicAssignmentsResponse.fromJson(Map<String, dynamic> json) =>
      _$AcademicAssignmentsResponseFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicAssignmentsResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
