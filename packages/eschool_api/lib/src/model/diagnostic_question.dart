//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/diagnostic_question_options_inner.dart';
import 'package:json_annotation/json_annotation.dart';

part 'diagnostic_question.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class DiagnosticQuestion {
  /// Returns a new [DiagnosticQuestion] instance.
  DiagnosticQuestion({
    required this.id,

    required this.position,

    required this.kind,

    required this.prompt,

    required this.options,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  // maximum: 20
  @JsonKey(name: r'position', required: true, includeIfNull: false)
  final int position;

  @JsonKey(
    name: r'kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue: DiagnosticQuestionKindEnum.unknownDefaultOpenApi,
  )
  final DiagnosticQuestionKindEnum kind;

  @JsonKey(name: r'prompt', required: true, includeIfNull: false)
  final String prompt;

  @JsonKey(name: r'options', required: true, includeIfNull: false)
  final List<DiagnosticQuestionOptionsInner> options;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is DiagnosticQuestion &&
          other.id == id &&
          other.position == position &&
          other.kind == kind &&
          other.prompt == prompt &&
          other.options == options;

  @override
  int get hashCode =>
      id.hashCode +
      position.hashCode +
      kind.hashCode +
      prompt.hashCode +
      options.hashCode;

  factory DiagnosticQuestion.fromJson(Map<String, dynamic> json) =>
      _$DiagnosticQuestionFromJson(json);

  Map<String, dynamic> toJson() => _$DiagnosticQuestionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum DiagnosticQuestionKindEnum {
  @JsonValue(r'choice')
  choice(r'choice'),
  @JsonValue(r'number')
  number(r'number'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const DiagnosticQuestionKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
