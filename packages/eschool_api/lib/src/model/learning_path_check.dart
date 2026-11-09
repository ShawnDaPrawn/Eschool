//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'learning_path_check.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class LearningPathCheck {
  /// Returns a new [LearningPathCheck] instance.
  LearningPathCheck({
    required this.correct,

    required this.total,

    required this.needed,

    required this.passed,
  });

  // minimum: 0
  @JsonKey(name: r'correct', required: true, includeIfNull: false)
  final int correct;

  // minimum: 1
  @JsonKey(name: r'total', required: true, includeIfNull: false)
  final int total;

  // minimum: 1
  @JsonKey(name: r'needed', required: true, includeIfNull: false)
  final int needed;

  @JsonKey(name: r'passed', required: true, includeIfNull: false)
  final bool passed;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is LearningPathCheck &&
          other.correct == correct &&
          other.total == total &&
          other.needed == needed &&
          other.passed == passed;

  @override
  int get hashCode =>
      correct.hashCode + total.hashCode + needed.hashCode + passed.hashCode;

  factory LearningPathCheck.fromJson(Map<String, dynamic> json) =>
      _$LearningPathCheckFromJson(json);

  Map<String, dynamic> toJson() => _$LearningPathCheckToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
