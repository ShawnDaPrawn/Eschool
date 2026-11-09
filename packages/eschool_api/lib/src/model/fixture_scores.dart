//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'fixture_scores.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FixtureScores {
  /// Returns a new [FixtureScores] instance.
  FixtureScores({required this.hostScore, required this.guestScore});

  @JsonKey(name: r'host_score', required: true, includeIfNull: false)
  final int hostScore;

  @JsonKey(name: r'guest_score', required: true, includeIfNull: false)
  final int guestScore;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FixtureScores &&
          other.hostScore == hostScore &&
          other.guestScore == guestScore;

  @override
  int get hashCode => hostScore.hashCode + guestScore.hashCode;

  factory FixtureScores.fromJson(Map<String, dynamic> json) =>
      _$FixtureScoresFromJson(json);

  Map<String, dynamic> toJson() => _$FixtureScoresToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
