//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/practice_question_option.dart';
import 'package:json_annotation/json_annotation.dart';

part 'practice_step.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PracticeStep {
  /// Returns a new [PracticeStep] instance.
  PracticeStep({
    this.id,

    required this.prompt,

    required this.kind,

    required this.options,

    required this.answers,
  });

  @JsonKey(name: r'id', required: false, includeIfNull: false)
  final String? id;

  @JsonKey(name: r'prompt', required: true, includeIfNull: false)
  final String prompt;

  @JsonKey(
    name: r'kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue: PracticeStepKindEnum.unknownDefaultOpenApi,
  )
  final PracticeStepKindEnum kind;

  @JsonKey(name: r'options', required: true, includeIfNull: false)
  final List<PracticeQuestionOption> options;

  @JsonKey(name: r'answers', required: true, includeIfNull: false)
  final List<String> answers;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PracticeStep &&
          other.id == id &&
          other.prompt == prompt &&
          other.kind == kind &&
          other.options == options &&
          other.answers == answers;

  @override
  int get hashCode =>
      id.hashCode +
      prompt.hashCode +
      kind.hashCode +
      options.hashCode +
      answers.hashCode;

  factory PracticeStep.fromJson(Map<String, dynamic> json) =>
      _$PracticeStepFromJson(json);

  Map<String, dynamic> toJson() => _$PracticeStepToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum PracticeStepKindEnum {
  @JsonValue(r'choice')
  choice(r'choice'),
  @JsonValue(r'number')
  number(r'number'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const PracticeStepKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
