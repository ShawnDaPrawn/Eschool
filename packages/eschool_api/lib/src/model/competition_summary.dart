//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'competition_summary.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CompetitionSummary {
  /// Returns a new [CompetitionSummary] instance.
  CompetitionSummary({
    required this.id,

    required this.kind,

    required this.name,

    required this.discipline,

    required this.season,

    required this.pointsWin,

    required this.pointsDraw,

    required this.pointsLoss,

    required this.organiser,

    required this.myRole,

    required this.myState,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'kind', required: true, includeIfNull: false)
  final String kind;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'discipline', required: true, includeIfNull: false)
  final String discipline;

  @JsonKey(name: r'season', required: true, includeIfNull: false)
  final String season;

  @JsonKey(name: r'points_win', required: true, includeIfNull: false)
  final int pointsWin;

  @JsonKey(name: r'points_draw', required: true, includeIfNull: false)
  final int pointsDraw;

  @JsonKey(name: r'points_loss', required: true, includeIfNull: false)
  final int pointsLoss;

  @JsonKey(name: r'organiser', required: true, includeIfNull: false)
  final String organiser;

  @JsonKey(
    name: r'my_role',
    required: true,
    includeIfNull: false,
    unknownEnumValue: CompetitionSummaryMyRoleEnum.unknownDefaultOpenApi,
  )
  final CompetitionSummaryMyRoleEnum myRole;

  @JsonKey(
    name: r'my_state',
    required: true,
    includeIfNull: false,
    unknownEnumValue: CompetitionSummaryMyStateEnum.unknownDefaultOpenApi,
  )
  final CompetitionSummaryMyStateEnum myState;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CompetitionSummary &&
          other.id == id &&
          other.kind == kind &&
          other.name == name &&
          other.discipline == discipline &&
          other.season == season &&
          other.pointsWin == pointsWin &&
          other.pointsDraw == pointsDraw &&
          other.pointsLoss == pointsLoss &&
          other.organiser == organiser &&
          other.myRole == myRole &&
          other.myState == myState;

  @override
  int get hashCode =>
      id.hashCode +
      kind.hashCode +
      name.hashCode +
      discipline.hashCode +
      season.hashCode +
      pointsWin.hashCode +
      pointsDraw.hashCode +
      pointsLoss.hashCode +
      organiser.hashCode +
      myRole.hashCode +
      myState.hashCode;

  factory CompetitionSummary.fromJson(Map<String, dynamic> json) =>
      _$CompetitionSummaryFromJson(json);

  Map<String, dynamic> toJson() => _$CompetitionSummaryToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum CompetitionSummaryMyRoleEnum {
  @JsonValue(r'organiser')
  organiser(r'organiser'),
  @JsonValue(r'member')
  member(r'member'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const CompetitionSummaryMyRoleEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum CompetitionSummaryMyStateEnum {
  @JsonValue(r'invited')
  invited(r'invited'),
  @JsonValue(r'accepted')
  accepted(r'accepted'),
  @JsonValue(r'declined')
  declined(r'declined'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const CompetitionSummaryMyStateEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
