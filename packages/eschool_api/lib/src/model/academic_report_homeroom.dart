//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_report_homeroom.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicReportHomeroom {
  /// Returns a new [AcademicReportHomeroom] instance.
  AcademicReportHomeroom({
    required this.id,

    required this.gradeLevelId,

    required this.gradeOrder,

    required this.name,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'grade_level_id', required: true, includeIfNull: false)
  final int gradeLevelId;

  // minimum: 0
  // maximum: 12
  @JsonKey(name: r'grade_order', required: true, includeIfNull: false)
  final int gradeOrder;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicReportHomeroom &&
          other.id == id &&
          other.gradeLevelId == gradeLevelId &&
          other.gradeOrder == gradeOrder &&
          other.name == name;

  @override
  int get hashCode =>
      id.hashCode + gradeLevelId.hashCode + gradeOrder.hashCode + name.hashCode;

  factory AcademicReportHomeroom.fromJson(Map<String, dynamic> json) =>
      _$AcademicReportHomeroomFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicReportHomeroomToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
