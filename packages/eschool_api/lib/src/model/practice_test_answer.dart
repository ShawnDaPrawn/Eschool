//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'practice_test_answer.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PracticeTestAnswer {
  /// Returns a new [PracticeTestAnswer] instance.
  PracticeTestAnswer({required this.questionId, required this.answer});

  // minimum: 1
  @JsonKey(name: r'question_id', required: true, includeIfNull: false)
  final int questionId;

  @JsonKey(name: r'answer', required: true, includeIfNull: true)
  final String? answer;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PracticeTestAnswer &&
          other.questionId == questionId &&
          other.answer == answer;

  @override
  int get hashCode =>
      questionId.hashCode + (answer == null ? 0 : answer.hashCode);

  factory PracticeTestAnswer.fromJson(Map<String, dynamic> json) =>
      _$PracticeTestAnswerFromJson(json);

  Map<String, dynamic> toJson() => _$PracticeTestAnswerToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
