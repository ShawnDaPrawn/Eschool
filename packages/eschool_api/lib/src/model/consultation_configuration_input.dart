//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/consultation_question_input.dart';
import 'package:eschool_api/src/model/consultation_audience_input.dart';
import 'package:json_annotation/json_annotation.dart';

part 'consultation_configuration_input.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ConsultationConfigurationInput {
  /// Returns a new [ConsultationConfigurationInput] instance.
  ConsultationConfigurationInput({
    required this.operationId,

    required this.revision,

    required this.sessionYearId,

    required this.type,

    required this.title,

    this.instructions,

    required this.opensAt,

    required this.closesAt,

    required this.anonymous,

    required this.resultsVisibility,

    required this.audience,

    required this.questions,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 0
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  @JsonKey(
    name: r'type',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        ConsultationConfigurationInputTypeEnum.unknownDefaultOpenApi,
  )
  final ConsultationConfigurationInputTypeEnum type;

  @JsonKey(name: r'title', required: true, includeIfNull: false)
  final String title;

  @JsonKey(name: r'instructions', required: false, includeIfNull: false)
  final String? instructions;

  @JsonKey(name: r'opens_at', required: true, includeIfNull: false)
  final DateTime opensAt;

  @JsonKey(name: r'closes_at', required: true, includeIfNull: false)
  final DateTime closesAt;

  @JsonKey(name: r'anonymous', required: true, includeIfNull: false)
  final bool anonymous;

  @JsonKey(
    name: r'results_visibility',
    required: true,
    includeIfNull: false,
    unknownEnumValue: ConsultationConfigurationInputResultsVisibilityEnum
        .unknownDefaultOpenApi,
  )
  final ConsultationConfigurationInputResultsVisibilityEnum resultsVisibility;

  @JsonKey(name: r'audience', required: true, includeIfNull: false)
  final ConsultationAudienceInput audience;

  @JsonKey(name: r'questions', required: true, includeIfNull: false)
  final List<ConsultationQuestionInput> questions;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ConsultationConfigurationInput &&
          other.operationId == operationId &&
          other.revision == revision &&
          other.sessionYearId == sessionYearId &&
          other.type == type &&
          other.title == title &&
          other.instructions == instructions &&
          other.opensAt == opensAt &&
          other.closesAt == closesAt &&
          other.anonymous == anonymous &&
          other.resultsVisibility == resultsVisibility &&
          other.audience == audience &&
          other.questions == questions;

  @override
  int get hashCode =>
      operationId.hashCode +
      revision.hashCode +
      sessionYearId.hashCode +
      type.hashCode +
      title.hashCode +
      (instructions == null ? 0 : instructions.hashCode) +
      opensAt.hashCode +
      closesAt.hashCode +
      anonymous.hashCode +
      resultsVisibility.hashCode +
      audience.hashCode +
      questions.hashCode;

  factory ConsultationConfigurationInput.fromJson(Map<String, dynamic> json) =>
      _$ConsultationConfigurationInputFromJson(json);

  Map<String, dynamic> toJson() => _$ConsultationConfigurationInputToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum ConsultationConfigurationInputTypeEnum {
  @JsonValue(r'poll')
  poll(r'poll'),
  @JsonValue(r'survey')
  survey(r'survey'),
  @JsonValue(r'election')
  election(r'election'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const ConsultationConfigurationInputTypeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum ConsultationConfigurationInputResultsVisibilityEnum {
  @JsonValue(r'after_close')
  afterClose(r'after_close'),
  @JsonValue(r'public_live')
  publicLive(r'public_live'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const ConsultationConfigurationInputResultsVisibilityEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
