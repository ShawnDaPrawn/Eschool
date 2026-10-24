//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_transition_assignment.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicTransitionAssignment {
  /// Returns a new [AcademicTransitionAssignment] instance.
  AcademicTransitionAssignment({
    required this.id,

    required this.name,

    required this.subjectName,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'subject_name', required: true, includeIfNull: false)
  final String subjectName;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicTransitionAssignment &&
          other.id == id &&
          other.name == name &&
          other.subjectName == subjectName;

  @override
  int get hashCode => id.hashCode + name.hashCode + subjectName.hashCode;

  factory AcademicTransitionAssignment.fromJson(Map<String, dynamic> json) =>
      _$AcademicTransitionAssignmentFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicTransitionAssignmentToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
