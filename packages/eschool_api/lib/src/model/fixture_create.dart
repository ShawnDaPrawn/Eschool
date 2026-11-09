//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'fixture_create.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FixtureCreate {
  /// Returns a new [FixtureCreate] instance.
  FixtureCreate({
    required this.operationId,

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
      other is FixtureCreate &&
          other.operationId == operationId &&
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

  factory FixtureCreate.fromJson(Map<String, dynamic> json) =>
      _$FixtureCreateFromJson(json);

  Map<String, dynamic> toJson() => _$FixtureCreateToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
