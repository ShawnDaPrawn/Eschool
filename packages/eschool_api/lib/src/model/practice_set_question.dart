//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/practice_set_question_diagram.dart';
import 'package:eschool_api/src/model/practice_question_option.dart';
import 'package:eschool_api/src/model/practice_set_step.dart';
import 'package:eschool_api/src/model/practice_diagram_region.dart';
import 'package:json_annotation/json_annotation.dart';

part 'practice_set_question.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PracticeSetQuestion {
  /// Returns a new [PracticeSetQuestion] instance.
  PracticeSetQuestion({
    required this.kind,

    required this.prompt,

    required this.options,

    this.steps,

    this.diagram,

    this.regions,

    this.labels,

    this.maxSeconds,

    this.maxLength,
  });

  @JsonKey(
    name: r'kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue: PracticeSetQuestionKindEnum.unknownDefaultOpenApi,
  )
  final PracticeSetQuestionKindEnum kind;

  @JsonKey(name: r'prompt', required: true, includeIfNull: false)
  final String prompt;

  @JsonKey(name: r'options', required: true, includeIfNull: false)
  final List<PracticeQuestionOption> options;

  @JsonKey(name: r'steps', required: false, includeIfNull: false)
  final List<PracticeSetStep>? steps;

  @JsonKey(name: r'diagram', required: false, includeIfNull: false)
  final PracticeSetQuestionDiagram? diagram;

  @JsonKey(name: r'regions', required: false, includeIfNull: false)
  final List<PracticeDiagramRegion>? regions;

  @JsonKey(name: r'labels', required: false, includeIfNull: false)
  final List<PracticeQuestionOption>? labels;

  // minimum: 10
  // maximum: 180
  @JsonKey(name: r'max_seconds', required: false, includeIfNull: false)
  final int? maxSeconds;

  // minimum: 1
  // maximum: 500
  @JsonKey(name: r'max_length', required: false, includeIfNull: false)
  final int? maxLength;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PracticeSetQuestion &&
          other.kind == kind &&
          other.prompt == prompt &&
          other.options == options &&
          other.steps == steps &&
          other.diagram == diagram &&
          other.regions == regions &&
          other.labels == labels &&
          other.maxSeconds == maxSeconds &&
          other.maxLength == maxLength;

  @override
  int get hashCode =>
      kind.hashCode +
      prompt.hashCode +
      options.hashCode +
      steps.hashCode +
      diagram.hashCode +
      regions.hashCode +
      labels.hashCode +
      maxSeconds.hashCode +
      maxLength.hashCode;

  factory PracticeSetQuestion.fromJson(Map<String, dynamic> json) =>
      _$PracticeSetQuestionFromJson(json);

  Map<String, dynamic> toJson() => _$PracticeSetQuestionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum PracticeSetQuestionKindEnum {
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

  const PracticeSetQuestionKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
