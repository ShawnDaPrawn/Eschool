//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/academic_report_design.dart';
import 'package:json_annotation/json_annotation.dart';

part 'academic_report_template.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicReportTemplate {
  /// Returns a new [AcademicReportTemplate] instance.
  AcademicReportTemplate({
    required this.id,

    required this.name,

    required this.revision,

    required this.design,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'design', required: true, includeIfNull: false)
  final AcademicReportDesign design;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicReportTemplate &&
          other.id == id &&
          other.name == name &&
          other.revision == revision &&
          other.design == design;

  @override
  int get hashCode =>
      id.hashCode + name.hashCode + revision.hashCode + design.hashCode;

  factory AcademicReportTemplate.fromJson(Map<String, dynamic> json) =>
      _$AcademicReportTemplateFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicReportTemplateToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
