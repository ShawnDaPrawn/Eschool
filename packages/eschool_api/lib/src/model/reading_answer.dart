//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'reading_answer.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ReadingAnswer {
  /// Returns a new [ReadingAnswer] instance.
  ReadingAnswer({
    required this.questionId,

    required this.choice,

    required this.correct,

    required this.answer,

    required this.explanation,
  });

  @JsonKey(name: r'question_id', required: true, includeIfNull: false)
  final int questionId;

  @JsonKey(name: r'choice', required: true, includeIfNull: false)
  final int choice;

  @JsonKey(name: r'correct', required: true, includeIfNull: false)
  final bool correct;

  @JsonKey(name: r'answer', required: true, includeIfNull: false)
  final int answer;

  @JsonKey(name: r'explanation', required: true, includeIfNull: true)
  final String? explanation;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ReadingAnswer &&
          other.questionId == questionId &&
          other.choice == choice &&
          other.correct == correct &&
          other.answer == answer &&
          other.explanation == explanation;

  @override
  int get hashCode =>
      questionId.hashCode +
      choice.hashCode +
      correct.hashCode +
      answer.hashCode +
      (explanation == null ? 0 : explanation.hashCode);

  factory ReadingAnswer.fromJson(Map<String, dynamic> json) =>
      _$ReadingAnswerFromJson(json);

  Map<String, dynamic> toJson() => _$ReadingAnswerToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
