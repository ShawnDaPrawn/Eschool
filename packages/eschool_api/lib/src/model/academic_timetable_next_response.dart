//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/academic_timetable_next_data.dart';
import 'package:json_annotation/json_annotation.dart';

part 'academic_timetable_next_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicTimetableNextResponse {
  /// Returns a new [AcademicTimetableNextResponse] instance.
  AcademicTimetableNextResponse({
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
  final AcademicTimetableNextData data;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicTimetableNextResponse &&
          other.error == error &&
          other.message == message &&
          other.code == code &&
          other.data == data;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + code.hashCode + data.hashCode;

  factory AcademicTimetableNextResponse.fromJson(Map<String, dynamic> json) =>
      _$AcademicTimetableNextResponseFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicTimetableNextResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
