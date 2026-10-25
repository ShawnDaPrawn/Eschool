//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/consultation_question.dart';
import 'package:eschool_api/src/model/consultation_audience.dart';
import 'package:json_annotation/json_annotation.dart';

part 'consultation_configuration.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ConsultationConfiguration {
  /// Returns a new [ConsultationConfiguration] instance.
  ConsultationConfiguration({
    required this.id,

    required this.revision,

    required this.status,

    required this.type,

    required this.title,

    required this.instructions,

    required this.sessionYearId,

    required this.anonymous,

    required this.resultsVisibility,

    required this.opensAt,

    required this.closesAt,

    required this.endedAt,

    required this.writable,

    required this.audience,

    required this.questions,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(
    name: r'status',
    required: true,
    includeIfNull: false,
    unknownEnumValue: ConsultationConfigurationStatusEnum.unknownDefaultOpenApi,
  )
  final ConsultationConfigurationStatusEnum status;

  @JsonKey(
    name: r'type',
    required: true,
    includeIfNull: false,
    unknownEnumValue: ConsultationConfigurationTypeEnum.unknownDefaultOpenApi,
  )
  final ConsultationConfigurationTypeEnum type;

  @JsonKey(name: r'title', required: true, includeIfNull: false)
  final String title;

  @JsonKey(name: r'instructions', required: true, includeIfNull: false)
  final String instructions;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  @JsonKey(name: r'anonymous', required: true, includeIfNull: false)
  final bool anonymous;

  @JsonKey(
    name: r'results_visibility',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        ConsultationConfigurationResultsVisibilityEnum.unknownDefaultOpenApi,
  )
  final ConsultationConfigurationResultsVisibilityEnum resultsVisibility;

  @JsonKey(name: r'opens_at', required: true, includeIfNull: false)
  final DateTime opensAt;

  @JsonKey(name: r'closes_at', required: true, includeIfNull: false)
  final DateTime closesAt;

  @JsonKey(name: r'ended_at', required: true, includeIfNull: true)
  final DateTime? endedAt;

  @JsonKey(name: r'writable', required: true, includeIfNull: false)
  final bool writable;

  @JsonKey(name: r'audience', required: true, includeIfNull: false)
  final ConsultationAudience audience;

  @JsonKey(name: r'questions', required: true, includeIfNull: false)
  final List<ConsultationQuestion> questions;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ConsultationConfiguration &&
          other.id == id &&
          other.revision == revision &&
          other.status == status &&
          other.type == type &&
          other.title == title &&
          other.instructions == instructions &&
          other.sessionYearId == sessionYearId &&
          other.anonymous == anonymous &&
          other.resultsVisibility == resultsVisibility &&
          other.opensAt == opensAt &&
          other.closesAt == closesAt &&
          other.endedAt == endedAt &&
          other.writable == writable &&
          other.audience == audience &&
          other.questions == questions;

  @override
  int get hashCode =>
      id.hashCode +
      revision.hashCode +
      status.hashCode +
      type.hashCode +
      title.hashCode +
      instructions.hashCode +
      sessionYearId.hashCode +
      anonymous.hashCode +
      resultsVisibility.hashCode +
      opensAt.hashCode +
      closesAt.hashCode +
      (endedAt == null ? 0 : endedAt.hashCode) +
      writable.hashCode +
      audience.hashCode +
      questions.hashCode;

  factory ConsultationConfiguration.fromJson(Map<String, dynamic> json) =>
      _$ConsultationConfigurationFromJson(json);

  Map<String, dynamic> toJson() => _$ConsultationConfigurationToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum ConsultationConfigurationStatusEnum {
  @JsonValue(r'draft')
  draft(r'draft'),
  @JsonValue(r'scheduled')
  scheduled(r'scheduled'),
  @JsonValue(r'open')
  open(r'open'),
  @JsonValue(r'closed')
  closed(r'closed'),
  @JsonValue(r'cancelled')
  cancelled(r'cancelled'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const ConsultationConfigurationStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum ConsultationConfigurationTypeEnum {
  @JsonValue(r'poll')
  poll(r'poll'),
  @JsonValue(r'survey')
  survey(r'survey'),
  @JsonValue(r'election')
  election(r'election'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const ConsultationConfigurationTypeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum ConsultationConfigurationResultsVisibilityEnum {
  @JsonValue(r'after_close')
  afterClose(r'after_close'),
  @JsonValue(r'public_live')
  publicLive(r'public_live'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const ConsultationConfigurationResultsVisibilityEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
