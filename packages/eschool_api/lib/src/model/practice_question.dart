//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/practice_question_option.dart';
import 'package:json_annotation/json_annotation.dart';

part 'practice_question.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PracticeQuestion {
  /// Returns a new [PracticeQuestion] instance.
  PracticeQuestion({
    required this.kind,

    required this.prompt,

    required this.explanation,

    required this.options,

    required this.answers,
  });

  @JsonKey(
    name: r'kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue: PracticeQuestionKindEnum.unknownDefaultOpenApi,
  )
  final PracticeQuestionKindEnum kind;

  @JsonKey(name: r'prompt', required: true, includeIfNull: false)
  final String prompt;

  @JsonKey(name: r'explanation', required: true, includeIfNull: false)
  final String explanation;

  @JsonKey(name: r'options', required: true, includeIfNull: false)
  final List<PracticeQuestionOption> options;

  @JsonKey(name: r'answers', required: true, includeIfNull: false)
  final List<String> answers;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PracticeQuestion &&
          other.kind == kind &&
          other.prompt == prompt &&
          other.explanation == explanation &&
          other.options == options &&
          other.answers == answers;

  @override
  int get hashCode =>
      kind.hashCode +
      prompt.hashCode +
      explanation.hashCode +
      options.hashCode +
      answers.hashCode;

  factory PracticeQuestion.fromJson(Map<String, dynamic> json) =>
      _$PracticeQuestionFromJson(json);

  Map<String, dynamic> toJson() => _$PracticeQuestionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum PracticeQuestionKindEnum {
  @JsonValue(r'choice')
  choice(r'choice'),
  @JsonValue(r'number')
  number(r'number'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const PracticeQuestionKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
