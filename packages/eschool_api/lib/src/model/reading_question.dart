//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/reading_result.dart';
import 'package:json_annotation/json_annotation.dart';

part 'reading_question.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ReadingQuestion {
  /// Returns a new [ReadingQuestion] instance.
  ReadingQuestion({
    required this.id,

    required this.afterPage,

    required this.prompt,

    required this.options,

    required this.result,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  /// The page that unlocks the question; null means the end of the book.
  @JsonKey(name: r'after_page', required: true, includeIfNull: true)
  final int? afterPage;

  @JsonKey(name: r'prompt', required: true, includeIfNull: false)
  final String prompt;

  @JsonKey(name: r'options', required: true, includeIfNull: false)
  final List<String> options;

  @JsonKey(name: r'result', required: true, includeIfNull: true)
  final ReadingResult? result;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ReadingQuestion &&
          other.id == id &&
          other.afterPage == afterPage &&
          other.prompt == prompt &&
          other.options == options &&
          other.result == result;

  @override
  int get hashCode =>
      id.hashCode +
      (afterPage == null ? 0 : afterPage.hashCode) +
      prompt.hashCode +
      options.hashCode +
      (result == null ? 0 : result.hashCode);

  factory ReadingQuestion.fromJson(Map<String, dynamic> json) =>
      _$ReadingQuestionFromJson(json);

  Map<String, dynamic> toJson() => _$ReadingQuestionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
