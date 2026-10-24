//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/academic_report_template.dart';
import 'package:json_annotation/json_annotation.dart';

part 'academic_report_template_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicReportTemplateResponse {
  /// Returns a new [AcademicReportTemplateResponse] instance.
  AcademicReportTemplateResponse({
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
  final AcademicReportTemplate data;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicReportTemplateResponse &&
          other.error == error &&
          other.message == message &&
          other.code == code &&
          other.data == data;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + code.hashCode + data.hashCode;

  factory AcademicReportTemplateResponse.fromJson(Map<String, dynamic> json) =>
      _$AcademicReportTemplateResponseFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicReportTemplateResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
