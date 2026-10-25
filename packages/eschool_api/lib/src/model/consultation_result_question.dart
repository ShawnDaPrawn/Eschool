//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/consultation_option_count.dart';
import 'package:eschool_api/src/model/consultation_option.dart';
import 'package:json_annotation/json_annotation.dart';

part 'consultation_result_question.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ConsultationResultQuestion {
  /// Returns a new [ConsultationResultQuestion] instance.
  ConsultationResultQuestion({
    required this.id,

    required this.kind,

    required this.label,

    required this.required_,

    required this.maxChoices,

    required this.options,

    required this.answered,

    required this.counts,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(
    name: r'kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue: ConsultationResultQuestionKindEnum.unknownDefaultOpenApi,
  )
  final ConsultationResultQuestionKindEnum kind;

  @JsonKey(name: r'label', required: true, includeIfNull: false)
  final String label;

  @JsonKey(name: r'required', required: true, includeIfNull: false)
  final bool required_;

  // minimum: 1
  @JsonKey(name: r'max_choices', required: true, includeIfNull: false)
  final int maxChoices;

  @JsonKey(name: r'options', required: true, includeIfNull: false)
  final List<ConsultationOption> options;

  // minimum: 0
  @JsonKey(name: r'answered', required: true, includeIfNull: false)
  final int answered;

  @JsonKey(name: r'counts', required: true, includeIfNull: false)
  final List<ConsultationOptionCount> counts;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ConsultationResultQuestion &&
          other.id == id &&
          other.kind == kind &&
          other.label == label &&
          other.required_ == required_ &&
          other.maxChoices == maxChoices &&
          other.options == options &&
          other.answered == answered &&
          other.counts == counts;

  @override
  int get hashCode =>
      id.hashCode +
      kind.hashCode +
      label.hashCode +
      required_.hashCode +
      maxChoices.hashCode +
      options.hashCode +
      answered.hashCode +
      counts.hashCode;

  factory ConsultationResultQuestion.fromJson(Map<String, dynamic> json) =>
      _$ConsultationResultQuestionFromJson(json);

  Map<String, dynamic> toJson() => _$ConsultationResultQuestionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum ConsultationResultQuestionKindEnum {
  @JsonValue(r'single')
  single(r'single'),
  @JsonValue(r'multiple')
  multiple(r'multiple'),
  @JsonValue(r'text')
  text(r'text'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const ConsultationResultQuestionKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
