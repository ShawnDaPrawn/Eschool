//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/academic_report_comment_data.dart';
import 'package:json_annotation/json_annotation.dart';

part 'academic_report_comment_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicReportCommentResponse {
  /// Returns a new [AcademicReportCommentResponse] instance.
  AcademicReportCommentResponse({
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
  final AcademicReportCommentData data;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicReportCommentResponse &&
          other.error == error &&
          other.message == message &&
          other.code == code &&
          other.data == data;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + code.hashCode + data.hashCode;

  factory AcademicReportCommentResponse.fromJson(Map<String, dynamic> json) =>
      _$AcademicReportCommentResponseFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicReportCommentResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
