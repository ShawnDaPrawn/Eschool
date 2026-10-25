//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/consultation_option.dart';
import 'package:json_annotation/json_annotation.dart';

part 'consultation_question.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ConsultationQuestion {
  /// Returns a new [ConsultationQuestion] instance.
  ConsultationQuestion({
    required this.id,

    required this.kind,

    required this.label,

    required this.required_,

    required this.maxChoices,

    required this.options,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(
    name: r'kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue: ConsultationQuestionKindEnum.unknownDefaultOpenApi,
  )
  final ConsultationQuestionKindEnum kind;

  @JsonKey(name: r'label', required: true, includeIfNull: false)
  final String label;

  @JsonKey(name: r'required', required: true, includeIfNull: false)
  final bool required_;

  // minimum: 1
  @JsonKey(name: r'max_choices', required: true, includeIfNull: false)
  final int maxChoices;

  @JsonKey(name: r'options', required: true, includeIfNull: false)
  final List<ConsultationOption> options;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ConsultationQuestion &&
          other.id == id &&
          other.kind == kind &&
          other.label == label &&
          other.required_ == required_ &&
          other.maxChoices == maxChoices &&
          other.options == options;

  @override
  int get hashCode =>
      id.hashCode +
      kind.hashCode +
      label.hashCode +
      required_.hashCode +
      maxChoices.hashCode +
      options.hashCode;

  factory ConsultationQuestion.fromJson(Map<String, dynamic> json) =>
      _$ConsultationQuestionFromJson(json);

  Map<String, dynamic> toJson() => _$ConsultationQuestionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum ConsultationQuestionKindEnum {
  @JsonValue(r'single')
  single(r'single'),
  @JsonValue(r'multiple')
  multiple(r'multiple'),
  @JsonValue(r'text')
  text(r'text'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const ConsultationQuestionKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
