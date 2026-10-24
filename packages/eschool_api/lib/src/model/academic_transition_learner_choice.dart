//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_transition_learner_choice.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicTransitionLearnerChoice {
  /// Returns a new [AcademicTransitionLearnerChoice] instance.
  AcademicTransitionLearnerChoice({
    required this.id,

    required this.name,

    required this.studentNumber,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'student_number', required: true, includeIfNull: true)
  final String? studentNumber;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicTransitionLearnerChoice &&
          other.id == id &&
          other.name == name &&
          other.studentNumber == studentNumber;

  @override
  int get hashCode =>
      id.hashCode +
      name.hashCode +
      (studentNumber == null ? 0 : studentNumber.hashCode);

  factory AcademicTransitionLearnerChoice.fromJson(Map<String, dynamic> json) =>
      _$AcademicTransitionLearnerChoiceFromJson(json);

  Map<String, dynamic> toJson() =>
      _$AcademicTransitionLearnerChoiceToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
