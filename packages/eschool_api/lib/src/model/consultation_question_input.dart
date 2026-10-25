//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/consultation_option_input.dart';
import 'package:json_annotation/json_annotation.dart';

part 'consultation_question_input.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ConsultationQuestionInput {
  /// Returns a new [ConsultationQuestionInput] instance.
  ConsultationQuestionInput({
    required this.kind,

    required this.label,

    required this.required_,

    this.maxChoices,

    this.options,
  });

  @JsonKey(
    name: r'kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue: ConsultationQuestionInputKindEnum.unknownDefaultOpenApi,
  )
  final ConsultationQuestionInputKindEnum kind;

  @JsonKey(name: r'label', required: true, includeIfNull: false)
  final String label;

  @JsonKey(name: r'required', required: true, includeIfNull: false)
  final bool required_;

  // minimum: 1
  // maximum: 50
  @JsonKey(name: r'max_choices', required: false, includeIfNull: false)
  final int? maxChoices;

  @JsonKey(name: r'options', required: false, includeIfNull: false)
  final List<ConsultationOptionInput>? options;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ConsultationQuestionInput &&
          other.kind == kind &&
          other.label == label &&
          other.required_ == required_ &&
          other.maxChoices == maxChoices &&
          other.options == options;

  @override
  int get hashCode =>
      kind.hashCode +
      label.hashCode +
      required_.hashCode +
      maxChoices.hashCode +
      options.hashCode;

  factory ConsultationQuestionInput.fromJson(Map<String, dynamic> json) =>
      _$ConsultationQuestionInputFromJson(json);

  Map<String, dynamic> toJson() => _$ConsultationQuestionInputToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum ConsultationQuestionInputKindEnum {
  @JsonValue(r'single')
  single(r'single'),
  @JsonValue(r'multiple')
  multiple(r'multiple'),
  @JsonValue(r'text')
  text(r'text'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const ConsultationQuestionInputKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
