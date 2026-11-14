//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_competition_create.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicCompetitionCreate {
  /// Returns a new [AcademicCompetitionCreate] instance.
  AcademicCompetitionCreate({
    required this.operationId,

    required this.kind,

    required this.motion,

    this.format,

    this.rounds,

    this.hostStance,

    required this.activityGroupId,

    required this.teamLabel,

    required this.discipline,

    this.sharedCompetitionId,

    this.guestSchoolId,

    this.externalOpponent,

    required this.onDate,

    this.startsAt,

    required this.venue,

    this.field,

    required this.hostIsHome,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @JsonKey(
    name: r'kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue: AcademicCompetitionCreateKindEnum.unknownDefaultOpenApi,
  )
  final AcademicCompetitionCreateKindEnum kind;

  @JsonKey(name: r'motion', required: true, includeIfNull: false)
  final String motion;

  @JsonKey(name: r'format', required: false, includeIfNull: false)
  final String? format;

  // minimum: 1
  // maximum: 20
  @JsonKey(name: r'rounds', required: false, includeIfNull: false)
  final int? rounds;

  @JsonKey(
    name: r'host_stance',
    required: false,
    includeIfNull: false,
    unknownEnumValue:
        AcademicCompetitionCreateHostStanceEnum.unknownDefaultOpenApi,
  )
  final AcademicCompetitionCreateHostStanceEnum? hostStance;

  // minimum: 1
  @JsonKey(name: r'activity_group_id', required: true, includeIfNull: false)
  final int activityGroupId;

  @JsonKey(name: r'team_label', required: true, includeIfNull: false)
  final String teamLabel;

  @JsonKey(name: r'discipline', required: true, includeIfNull: false)
  final String discipline;

  // minimum: 1
  @JsonKey(
    name: r'shared_competition_id',
    required: false,
    includeIfNull: false,
  )
  final int? sharedCompetitionId;

  // minimum: 1
  @JsonKey(name: r'guest_school_id', required: false, includeIfNull: false)
  final int? guestSchoolId;

  @JsonKey(name: r'external_opponent', required: false, includeIfNull: false)
  final String? externalOpponent;

  @JsonKey(name: r'on_date', required: true, includeIfNull: false)
  final String onDate;

  @JsonKey(name: r'starts_at', required: false, includeIfNull: false)
  final String? startsAt;

  @JsonKey(name: r'venue', required: true, includeIfNull: false)
  final String venue;

  @JsonKey(name: r'field', required: false, includeIfNull: false)
  final String? field;

  @JsonKey(name: r'host_is_home', required: true, includeIfNull: false)
  final bool hostIsHome;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicCompetitionCreate &&
          other.operationId == operationId &&
          other.kind == kind &&
          other.motion == motion &&
          other.format == format &&
          other.rounds == rounds &&
          other.hostStance == hostStance &&
          other.activityGroupId == activityGroupId &&
          other.teamLabel == teamLabel &&
          other.discipline == discipline &&
          other.sharedCompetitionId == sharedCompetitionId &&
          other.guestSchoolId == guestSchoolId &&
          other.externalOpponent == externalOpponent &&
          other.onDate == onDate &&
          other.startsAt == startsAt &&
          other.venue == venue &&
          other.field == field &&
          other.hostIsHome == hostIsHome;

  @override
  int get hashCode =>
      operationId.hashCode +
      kind.hashCode +
      motion.hashCode +
      (format == null ? 0 : format.hashCode) +
      (rounds == null ? 0 : rounds.hashCode) +
      (hostStance == null ? 0 : hostStance.hashCode) +
      activityGroupId.hashCode +
      teamLabel.hashCode +
      discipline.hashCode +
      (sharedCompetitionId == null ? 0 : sharedCompetitionId.hashCode) +
      (guestSchoolId == null ? 0 : guestSchoolId.hashCode) +
      (externalOpponent == null ? 0 : externalOpponent.hashCode) +
      onDate.hashCode +
      (startsAt == null ? 0 : startsAt.hashCode) +
      venue.hashCode +
      (field == null ? 0 : field.hashCode) +
      hostIsHome.hashCode;

  factory AcademicCompetitionCreate.fromJson(Map<String, dynamic> json) =>
      _$AcademicCompetitionCreateFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicCompetitionCreateToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum AcademicCompetitionCreateKindEnum {
  @JsonValue(r'debate')
  debate(r'debate'),
  @JsonValue(r'quiz')
  quiz(r'quiz'),
  @JsonValue(r'speech')
  speech(r'speech'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const AcademicCompetitionCreateKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum AcademicCompetitionCreateHostStanceEnum {
  @JsonValue(r'proposition')
  proposition(r'proposition'),
  @JsonValue(r'opposition')
  opposition(r'opposition'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const AcademicCompetitionCreateHostStanceEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
