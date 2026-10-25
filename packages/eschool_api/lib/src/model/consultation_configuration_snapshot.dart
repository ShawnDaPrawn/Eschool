//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/consultation_question.dart';
import 'package:eschool_api/src/model/consultation_audience.dart';
import 'package:json_annotation/json_annotation.dart';

part 'consultation_configuration_snapshot.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ConsultationConfigurationSnapshot {
  /// Returns a new [ConsultationConfigurationSnapshot] instance.
  ConsultationConfigurationSnapshot({
    required this.type,

    required this.title,

    required this.audience,

    required this.anonymous,

    required this.resultsVisibility,

    required this.status,

    required this.opensAt,

    required this.closesAt,

    required this.reason,

    required this.questions,

    this.instructions,

    this.sessionYearId,
  });

  @JsonKey(
    name: r'type',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        ConsultationConfigurationSnapshotTypeEnum.unknownDefaultOpenApi,
  )
  final ConsultationConfigurationSnapshotTypeEnum type;

  @JsonKey(name: r'title', required: true, includeIfNull: false)
  final String title;

  @JsonKey(name: r'audience', required: true, includeIfNull: false)
  final ConsultationAudience audience;

  @JsonKey(name: r'anonymous', required: true, includeIfNull: false)
  final bool anonymous;

  @JsonKey(
    name: r'results_visibility',
    required: true,
    includeIfNull: false,
    unknownEnumValue: ConsultationConfigurationSnapshotResultsVisibilityEnum
        .unknownDefaultOpenApi,
  )
  final ConsultationConfigurationSnapshotResultsVisibilityEnum
  resultsVisibility;

  @JsonKey(
    name: r'status',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        ConsultationConfigurationSnapshotStatusEnum.unknownDefaultOpenApi,
  )
  final ConsultationConfigurationSnapshotStatusEnum status;

  @JsonKey(name: r'opens_at', required: true, includeIfNull: false)
  final DateTime opensAt;

  @JsonKey(name: r'closes_at', required: true, includeIfNull: false)
  final DateTime closesAt;

  @JsonKey(name: r'reason', required: true, includeIfNull: true)
  final String? reason;

  @JsonKey(name: r'questions', required: true, includeIfNull: false)
  final List<ConsultationQuestion> questions;

  @JsonKey(name: r'instructions', required: false, includeIfNull: false)
  final String? instructions;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: false, includeIfNull: false)
  final int? sessionYearId;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ConsultationConfigurationSnapshot &&
          other.type == type &&
          other.title == title &&
          other.audience == audience &&
          other.anonymous == anonymous &&
          other.resultsVisibility == resultsVisibility &&
          other.status == status &&
          other.opensAt == opensAt &&
          other.closesAt == closesAt &&
          other.reason == reason &&
          other.questions == questions &&
          other.instructions == instructions &&
          other.sessionYearId == sessionYearId;

  @override
  int get hashCode =>
      type.hashCode +
      title.hashCode +
      audience.hashCode +
      anonymous.hashCode +
      resultsVisibility.hashCode +
      status.hashCode +
      opensAt.hashCode +
      closesAt.hashCode +
      (reason == null ? 0 : reason.hashCode) +
      questions.hashCode +
      instructions.hashCode +
      sessionYearId.hashCode;

  factory ConsultationConfigurationSnapshot.fromJson(
    Map<String, dynamic> json,
  ) => _$ConsultationConfigurationSnapshotFromJson(json);

  Map<String, dynamic> toJson() =>
      _$ConsultationConfigurationSnapshotToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum ConsultationConfigurationSnapshotTypeEnum {
  @JsonValue(r'poll')
  poll(r'poll'),
  @JsonValue(r'survey')
  survey(r'survey'),
  @JsonValue(r'election')
  election(r'election'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const ConsultationConfigurationSnapshotTypeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum ConsultationConfigurationSnapshotResultsVisibilityEnum {
  @JsonValue(r'after_close')
  afterClose(r'after_close'),
  @JsonValue(r'public_live')
  publicLive(r'public_live'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const ConsultationConfigurationSnapshotResultsVisibilityEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum ConsultationConfigurationSnapshotStatusEnum {
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

  const ConsultationConfigurationSnapshotStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
