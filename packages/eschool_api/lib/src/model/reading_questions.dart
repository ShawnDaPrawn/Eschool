//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/reading_question.dart';
import 'package:json_annotation/json_annotation.dart';

part 'reading_questions.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ReadingQuestions {
  /// Returns a new [ReadingQuestions] instance.
  ReadingQuestions({
    required this.bookId,

    required this.questions,

    required this.locked,
  });

  @JsonKey(name: r'book_id', required: true, includeIfNull: false)
  final int bookId;

  @JsonKey(name: r'questions', required: true, includeIfNull: false)
  final List<ReadingQuestion> questions;

  /// How many questions are still locked.
  @JsonKey(name: r'locked', required: true, includeIfNull: false)
  final int locked;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ReadingQuestions &&
          other.bookId == bookId &&
          other.questions == questions &&
          other.locked == locked;

  @override
  int get hashCode => bookId.hashCode + questions.hashCode + locked.hashCode;

  factory ReadingQuestions.fromJson(Map<String, dynamic> json) =>
      _$ReadingQuestionsFromJson(json);

  Map<String, dynamic> toJson() => _$ReadingQuestionsToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
