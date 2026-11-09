//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/practice_step_solution.dart';
import 'package:eschool_api/src/model/practice_region_solution.dart';
import 'package:json_annotation/json_annotation.dart';

part 'practice_solution.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PracticeSolution {
  /// Returns a new [PracticeSolution] instance.
  PracticeSolution({
    required this.explanation,

    required this.answers,

    required this.steps,

    required this.mapping,
  });

  @JsonKey(name: r'explanation', required: true, includeIfNull: false)
  final String explanation;

  @JsonKey(name: r'answers', required: true, includeIfNull: false)
  final List<String> answers;

  @JsonKey(name: r'steps', required: true, includeIfNull: false)
  final List<PracticeStepSolution> steps;

  @JsonKey(name: r'mapping', required: true, includeIfNull: false)
  final List<PracticeRegionSolution> mapping;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PracticeSolution &&
          other.explanation == explanation &&
          other.answers == answers &&
          other.steps == steps &&
          other.mapping == mapping;

  @override
  int get hashCode =>
      explanation.hashCode +
      answers.hashCode +
      steps.hashCode +
      mapping.hashCode;

  factory PracticeSolution.fromJson(Map<String, dynamic> json) =>
      _$PracticeSolutionFromJson(json);

  Map<String, dynamic> toJson() => _$PracticeSolutionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
