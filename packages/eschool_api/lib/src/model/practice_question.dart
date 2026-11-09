//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/practice_step.dart';
import 'package:eschool_api/src/model/practice_question_option.dart';
import 'package:eschool_api/src/model/practice_diagram_reference.dart';
import 'package:eschool_api/src/model/practice_diagram_region.dart';
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

    this.steps,

    this.diagram,

    this.regions,

    this.labels,

    this.mapping,

    this.maxSeconds,
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

  @JsonKey(name: r'steps', required: false, includeIfNull: false)
  final List<PracticeStep>? steps;

  @JsonKey(name: r'diagram', required: false, includeIfNull: false)
  final PracticeDiagramReference? diagram;

  @JsonKey(name: r'regions', required: false, includeIfNull: false)
  final List<PracticeDiagramRegion>? regions;

  @JsonKey(name: r'labels', required: false, includeIfNull: false)
  final List<PracticeQuestionOption>? labels;

  /// Region code to the correct label code.
  @JsonKey(name: r'mapping', required: false, includeIfNull: false)
  final Map<String, String>? mapping;

  // minimum: 10
  // maximum: 180
  @JsonKey(name: r'max_seconds', required: false, includeIfNull: false)
  final int? maxSeconds;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PracticeQuestion &&
          other.kind == kind &&
          other.prompt == prompt &&
          other.explanation == explanation &&
          other.options == options &&
          other.answers == answers &&
          other.steps == steps &&
          other.diagram == diagram &&
          other.regions == regions &&
          other.labels == labels &&
          other.mapping == mapping &&
          other.maxSeconds == maxSeconds;

  @override
  int get hashCode =>
      kind.hashCode +
      prompt.hashCode +
      explanation.hashCode +
      options.hashCode +
      answers.hashCode +
      steps.hashCode +
      diagram.hashCode +
      regions.hashCode +
      labels.hashCode +
      mapping.hashCode +
      maxSeconds.hashCode;

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
  @JsonValue(r'steps')
  steps(r'steps'),
  @JsonValue(r'short_text')
  shortText(r'short_text'),
  @JsonValue(r'diagram_label')
  diagramLabel(r'diagram_label'),
  @JsonValue(r'audio')
  audio(r'audio'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const PracticeQuestionKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
