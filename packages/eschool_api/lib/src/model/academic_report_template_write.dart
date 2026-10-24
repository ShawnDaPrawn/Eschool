//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/academic_report_design.dart';
import 'package:json_annotation/json_annotation.dart';

part 'academic_report_template_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicReportTemplateWrite {
  /// Returns a new [AcademicReportTemplateWrite] instance.
  AcademicReportTemplateWrite({
    this.id,

    this.revision,

    required this.name,

    required this.design,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: false, includeIfNull: false)
  final int? id;

  // minimum: 1
  @JsonKey(name: r'revision', required: false, includeIfNull: false)
  final int? revision;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'design', required: true, includeIfNull: false)
  final AcademicReportDesign design;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicReportTemplateWrite &&
          other.id == id &&
          other.revision == revision &&
          other.name == name &&
          other.design == design;

  @override
  int get hashCode =>
      id.hashCode + revision.hashCode + name.hashCode + design.hashCode;

  factory AcademicReportTemplateWrite.fromJson(Map<String, dynamic> json) =>
      _$AcademicReportTemplateWriteFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicReportTemplateWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
