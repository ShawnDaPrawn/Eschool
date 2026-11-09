//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'reading_result.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ReadingResult {
  /// Returns a new [ReadingResult] instance.
  ReadingResult({
    required this.choice,

    required this.correct,

    required this.answer,

    required this.explanation,
  });

  @JsonKey(name: r'choice', required: true, includeIfNull: false)
  final int choice;

  @JsonKey(name: r'correct', required: true, includeIfNull: false)
  final bool correct;

  /// The index of the right option.
  @JsonKey(name: r'answer', required: true, includeIfNull: false)
  final int answer;

  @JsonKey(name: r'explanation', required: true, includeIfNull: true)
  final String? explanation;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ReadingResult &&
          other.choice == choice &&
          other.correct == correct &&
          other.answer == answer &&
          other.explanation == explanation;

  @override
  int get hashCode =>
      choice.hashCode +
      correct.hashCode +
      answer.hashCode +
      (explanation == null ? 0 : explanation.hashCode);

  factory ReadingResult.fromJson(Map<String, dynamic> json) =>
      _$ReadingResultFromJson(json);

  Map<String, dynamic> toJson() => _$ReadingResultToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
