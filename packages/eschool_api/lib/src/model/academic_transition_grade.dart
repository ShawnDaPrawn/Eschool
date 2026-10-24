//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/academic_transition_grade_policy.dart';
import 'package:json_annotation/json_annotation.dart';

part 'academic_transition_grade.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicTransitionGrade {
  /// Returns a new [AcademicTransitionGrade] instance.
  AcademicTransitionGrade({
    required this.id,

    required this.name,

    required this.policy,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'policy', required: true, includeIfNull: false)
  final AcademicTransitionGradePolicy policy;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicTransitionGrade &&
          other.id == id &&
          other.name == name &&
          other.policy == policy;

  @override
  int get hashCode => id.hashCode + name.hashCode + policy.hashCode;

  factory AcademicTransitionGrade.fromJson(Map<String, dynamic> json) =>
      _$AcademicTransitionGradeFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicTransitionGradeToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
