//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_report_term.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicReportTerm {
  /// Returns a new [AcademicReportTerm] instance.
  AcademicReportTerm({
    required this.id,

    required this.name,

    required this.gradeFrom,

    required this.gradeTo,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  // minimum: 0
  // maximum: 12
  @JsonKey(name: r'grade_from', required: true, includeIfNull: false)
  final int gradeFrom;

  // minimum: 0
  // maximum: 12
  @JsonKey(name: r'grade_to', required: true, includeIfNull: false)
  final int gradeTo;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicReportTerm &&
          other.id == id &&
          other.name == name &&
          other.gradeFrom == gradeFrom &&
          other.gradeTo == gradeTo;

  @override
  int get hashCode =>
      id.hashCode + name.hashCode + gradeFrom.hashCode + gradeTo.hashCode;

  factory AcademicReportTerm.fromJson(Map<String, dynamic> json) =>
      _$AcademicReportTermFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicReportTermToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
