//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_question_score.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicQuestionScore {
  /// Returns a new [AcademicQuestionScore] instance.
  AcademicQuestionScore({required this.questionId, required this.mark});

  // minimum: 1
  @JsonKey(name: r'question_id', required: true, includeIfNull: false)
  final int questionId;

  @JsonKey(name: r'mark', required: true, includeIfNull: true)
  final String? mark;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicQuestionScore &&
          other.questionId == questionId &&
          other.mark == mark;

  @override
  int get hashCode => questionId.hashCode + (mark == null ? 0 : mark.hashCode);

  factory AcademicQuestionScore.fromJson(Map<String, dynamic> json) =>
      _$AcademicQuestionScoreFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicQuestionScoreToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
