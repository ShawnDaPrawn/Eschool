//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'practice_step_solution.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PracticeStepSolution {
  /// Returns a new [PracticeStepSolution] instance.
  PracticeStepSolution({required this.id, required this.answers});

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final String id;

  @JsonKey(name: r'answers', required: true, includeIfNull: false)
  final List<String> answers;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PracticeStepSolution &&
          other.id == id &&
          other.answers == answers;

  @override
  int get hashCode => id.hashCode + answers.hashCode;

  factory PracticeStepSolution.fromJson(Map<String, dynamic> json) =>
      _$PracticeStepSolutionFromJson(json);

  Map<String, dynamic> toJson() => _$PracticeStepSolutionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
