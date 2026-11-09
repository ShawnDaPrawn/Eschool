//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/learner_fixture_result.dart';
import 'package:json_annotation/json_annotation.dart';

part 'learner_fixture.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class LearnerFixture {
  /// Returns a new [LearnerFixture] instance.
  LearnerFixture({
    required this.id,

    required this.discipline,

    required this.onDate,

    required this.startsAt,

    required this.venue,

    required this.field,

    required this.status,

    required this.competition,

    required this.teamLabel,

    required this.opponent,

    required this.home,

    required this.result,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'discipline', required: true, includeIfNull: false)
  final String discipline;

  @JsonKey(name: r'on_date', required: true, includeIfNull: false)
  final String onDate;

  @JsonKey(name: r'starts_at', required: true, includeIfNull: true)
  final String? startsAt;

  @JsonKey(name: r'venue', required: true, includeIfNull: false)
  final String venue;

  @JsonKey(name: r'field', required: true, includeIfNull: true)
  final String? field;

  @JsonKey(name: r'status', required: true, includeIfNull: false)
  final String status;

  @JsonKey(name: r'competition', required: true, includeIfNull: true)
  final String? competition;

  @JsonKey(name: r'team_label', required: true, includeIfNull: false)
  final String teamLabel;

  @JsonKey(name: r'opponent', required: true, includeIfNull: false)
  final String opponent;

  @JsonKey(name: r'home', required: true, includeIfNull: false)
  final bool home;

  @JsonKey(name: r'result', required: true, includeIfNull: false)
  final LearnerFixtureResult result;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is LearnerFixture &&
          other.id == id &&
          other.discipline == discipline &&
          other.onDate == onDate &&
          other.startsAt == startsAt &&
          other.venue == venue &&
          other.field == field &&
          other.status == status &&
          other.competition == competition &&
          other.teamLabel == teamLabel &&
          other.opponent == opponent &&
          other.home == home &&
          other.result == result;

  @override
  int get hashCode =>
      id.hashCode +
      discipline.hashCode +
      onDate.hashCode +
      (startsAt == null ? 0 : startsAt.hashCode) +
      venue.hashCode +
      (field == null ? 0 : field.hashCode) +
      status.hashCode +
      (competition == null ? 0 : competition.hashCode) +
      teamLabel.hashCode +
      opponent.hashCode +
      home.hashCode +
      result.hashCode;

  factory LearnerFixture.fromJson(Map<String, dynamic> json) =>
      _$LearnerFixtureFromJson(json);

  Map<String, dynamic> toJson() => _$LearnerFixtureToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
