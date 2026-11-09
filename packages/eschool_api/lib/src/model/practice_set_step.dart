//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/practice_question_option.dart';
import 'package:json_annotation/json_annotation.dart';

part 'practice_set_step.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PracticeSetStep {
  /// Returns a new [PracticeSetStep] instance.
  PracticeSetStep({
    required this.id,

    required this.prompt,

    required this.kind,

    required this.options,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final String id;

  @JsonKey(name: r'prompt', required: true, includeIfNull: false)
  final String prompt;

  @JsonKey(
    name: r'kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue: PracticeSetStepKindEnum.unknownDefaultOpenApi,
  )
  final PracticeSetStepKindEnum kind;

  @JsonKey(name: r'options', required: true, includeIfNull: false)
  final List<PracticeQuestionOption> options;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PracticeSetStep &&
          other.id == id &&
          other.prompt == prompt &&
          other.kind == kind &&
          other.options == options;

  @override
  int get hashCode =>
      id.hashCode + prompt.hashCode + kind.hashCode + options.hashCode;

  factory PracticeSetStep.fromJson(Map<String, dynamic> json) =>
      _$PracticeSetStepFromJson(json);

  Map<String, dynamic> toJson() => _$PracticeSetStepToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum PracticeSetStepKindEnum {
  @JsonValue(r'choice')
  choice(r'choice'),
  @JsonValue(r'number')
  number(r'number'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const PracticeSetStepKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
