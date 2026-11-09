//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'competition_standings_inner.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CompetitionStandingsInner {
  /// Returns a new [CompetitionStandingsInner] instance.
  CompetitionStandingsInner({
    required this.team,

    required this.played,

    required this.won,

    required this.drawn,

    required this.lost,

    required this.scored,

    required this.conceded,

    required this.difference,

    required this.points,
  });

  @JsonKey(name: r'team', required: true, includeIfNull: false)
  final String team;

  @JsonKey(name: r'played', required: true, includeIfNull: false)
  final int played;

  @JsonKey(name: r'won', required: true, includeIfNull: false)
  final int won;

  @JsonKey(name: r'drawn', required: true, includeIfNull: false)
  final int drawn;

  @JsonKey(name: r'lost', required: true, includeIfNull: false)
  final int lost;

  @JsonKey(name: r'scored', required: true, includeIfNull: false)
  final int scored;

  @JsonKey(name: r'conceded', required: true, includeIfNull: false)
  final int conceded;

  @JsonKey(name: r'difference', required: true, includeIfNull: false)
  final int difference;

  @JsonKey(name: r'points', required: true, includeIfNull: false)
  final int points;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CompetitionStandingsInner &&
          other.team == team &&
          other.played == played &&
          other.won == won &&
          other.drawn == drawn &&
          other.lost == lost &&
          other.scored == scored &&
          other.conceded == conceded &&
          other.difference == difference &&
          other.points == points;

  @override
  int get hashCode =>
      team.hashCode +
      played.hashCode +
      won.hashCode +
      drawn.hashCode +
      lost.hashCode +
      scored.hashCode +
      conceded.hashCode +
      difference.hashCode +
      points.hashCode;

  factory CompetitionStandingsInner.fromJson(Map<String, dynamic> json) =>
      _$CompetitionStandingsInnerFromJson(json);

  Map<String, dynamic> toJson() => _$CompetitionStandingsInnerToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
