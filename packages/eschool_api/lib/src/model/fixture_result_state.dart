//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/fixture_scores.dart';
import 'package:json_annotation/json_annotation.dart';

part 'fixture_result_state.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FixtureResultState {
  /// Returns a new [FixtureResultState] instance.
  FixtureResultState({
    required this.state,

    required this.hostScore,

    required this.guestScore,

    this.winner,

    this.myReport,

    this.theirReport,

    this.waitingFor,
  });

  @JsonKey(
    name: r'state',
    required: true,
    includeIfNull: false,
    unknownEnumValue: FixtureResultStateStateEnum.unknownDefaultOpenApi,
  )
  final FixtureResultStateStateEnum state;

  @JsonKey(name: r'host_score', required: true, includeIfNull: true)
  final int? hostScore;

  @JsonKey(name: r'guest_score', required: true, includeIfNull: true)
  final int? guestScore;

  /// The winning side both schools confirmed, for an academic competition (null for a match or before confirmation).
  @JsonKey(
    name: r'winner',
    required: false,
    includeIfNull: false,
    unknownEnumValue: FixtureResultStateWinnerEnum.unknownDefaultOpenApi,
  )
  final FixtureResultStateWinnerEnum? winner;

  @JsonKey(name: r'my_report', required: false, includeIfNull: false)
  final FixtureScores? myReport;

  @JsonKey(name: r'their_report', required: false, includeIfNull: false)
  final FixtureScores? theirReport;

  @JsonKey(
    name: r'waiting_for',
    required: false,
    includeIfNull: false,
    unknownEnumValue: FixtureResultStateWaitingForEnum.unknownDefaultOpenApi,
  )
  final FixtureResultStateWaitingForEnum? waitingFor;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FixtureResultState &&
          other.state == state &&
          other.hostScore == hostScore &&
          other.guestScore == guestScore &&
          other.winner == winner &&
          other.myReport == myReport &&
          other.theirReport == theirReport &&
          other.waitingFor == waitingFor;

  @override
  int get hashCode =>
      state.hashCode +
      (hostScore == null ? 0 : hostScore.hashCode) +
      (guestScore == null ? 0 : guestScore.hashCode) +
      (winner == null ? 0 : winner.hashCode) +
      (myReport == null ? 0 : myReport.hashCode) +
      (theirReport == null ? 0 : theirReport.hashCode) +
      (waitingFor == null ? 0 : waitingFor.hashCode);

  factory FixtureResultState.fromJson(Map<String, dynamic> json) =>
      _$FixtureResultStateFromJson(json);

  Map<String, dynamic> toJson() => _$FixtureResultStateToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum FixtureResultStateStateEnum {
  @JsonValue(r'none')
  none(r'none'),
  @JsonValue(r'awaiting')
  awaiting(r'awaiting'),
  @JsonValue(r'disputed')
  disputed(r'disputed'),
  @JsonValue(r'confirmed')
  confirmed(r'confirmed'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const FixtureResultStateStateEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

/// The winning side both schools confirmed, for an academic competition (null for a match or before confirmation).
enum FixtureResultStateWinnerEnum {
  @JsonValue(r'host')
  host(r'host'),
  @JsonValue(r'guest')
  guest(r'guest'),
  @JsonValue(r'draw')
  draw(r'draw'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const FixtureResultStateWinnerEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum FixtureResultStateWaitingForEnum {
  @JsonValue(r'us')
  us(r'us'),
  @JsonValue(r'them')
  them(r'them'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const FixtureResultStateWaitingForEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
