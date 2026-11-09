//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/practice_test_option.dart';
import 'package:json_annotation/json_annotation.dart';

part 'practice_test_question.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PracticeTestQuestion {
  /// Returns a new [PracticeTestQuestion] instance.
  PracticeTestQuestion({
    required this.id,

    required this.position,

    required this.topicId,

    required this.kind,

    required this.prompt,

    required this.options,

    required this.answer,

    this.marking,

    this.correct,

    this.acceptedAnswers,

    this.explanation,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'position', required: true, includeIfNull: false)
  final int position;

  // minimum: 1
  @JsonKey(name: r'topic_id', required: true, includeIfNull: false)
  final int topicId;

  @JsonKey(
    name: r'kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue: PracticeTestQuestionKindEnum.unknownDefaultOpenApi,
  )
  final PracticeTestQuestionKindEnum kind;

  @JsonKey(name: r'prompt', required: true, includeIfNull: false)
  final String prompt;

  @JsonKey(name: r'options', required: true, includeIfNull: false)
  final List<PracticeTestOption> options;

  @JsonKey(name: r'answer', required: true, includeIfNull: true)
  final String? answer;

  @JsonKey(
    name: r'marking',
    required: false,
    includeIfNull: false,
    unknownEnumValue: PracticeTestQuestionMarkingEnum.unknownDefaultOpenApi,
  )
  final PracticeTestQuestionMarkingEnum? marking;

  @JsonKey(name: r'correct', required: false, includeIfNull: false)
  final bool? correct;

  @JsonKey(name: r'accepted_answers', required: false, includeIfNull: false)
  final List<String>? acceptedAnswers;

  @JsonKey(name: r'explanation', required: false, includeIfNull: false)
  final String? explanation;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PracticeTestQuestion &&
          other.id == id &&
          other.position == position &&
          other.topicId == topicId &&
          other.kind == kind &&
          other.prompt == prompt &&
          other.options == options &&
          other.answer == answer &&
          other.marking == marking &&
          other.correct == correct &&
          other.acceptedAnswers == acceptedAnswers &&
          other.explanation == explanation;

  @override
  int get hashCode =>
      id.hashCode +
      position.hashCode +
      topicId.hashCode +
      kind.hashCode +
      prompt.hashCode +
      options.hashCode +
      (answer == null ? 0 : answer.hashCode) +
      (marking == null ? 0 : marking.hashCode) +
      (correct == null ? 0 : correct.hashCode) +
      acceptedAnswers.hashCode +
      explanation.hashCode;

  factory PracticeTestQuestion.fromJson(Map<String, dynamic> json) =>
      _$PracticeTestQuestionFromJson(json);

  Map<String, dynamic> toJson() => _$PracticeTestQuestionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum PracticeTestQuestionKindEnum {
  @JsonValue(r'choice')
  choice(r'choice'),
  @JsonValue(r'number')
  number(r'number'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const PracticeTestQuestionKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum PracticeTestQuestionMarkingEnum {
  @JsonValue(r'auto')
  auto(r'auto'),
  @JsonValue(r'awaiting_marker')
  awaitingMarker(r'awaiting_marker'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const PracticeTestQuestionMarkingEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
