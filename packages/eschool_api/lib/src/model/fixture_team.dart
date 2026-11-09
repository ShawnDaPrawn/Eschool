//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'fixture_team.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FixtureTeam {
  /// Returns a new [FixtureTeam] instance.
  FixtureTeam({
    required this.name,

    required this.external_,

    required this.state,

    this.teamLabel,
  });

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'external', required: true, includeIfNull: false)
  final bool external_;

  @JsonKey(
    name: r'state',
    required: true,
    includeIfNull: false,
    unknownEnumValue: FixtureTeamStateEnum.unknownDefaultOpenApi,
  )
  final FixtureTeamStateEnum state;

  @JsonKey(name: r'team_label', required: false, includeIfNull: false)
  final String? teamLabel;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FixtureTeam &&
          other.name == name &&
          other.external_ == external_ &&
          other.state == state &&
          other.teamLabel == teamLabel;

  @override
  int get hashCode =>
      name.hashCode +
      external_.hashCode +
      state.hashCode +
      (teamLabel == null ? 0 : teamLabel.hashCode);

  factory FixtureTeam.fromJson(Map<String, dynamic> json) =>
      _$FixtureTeamFromJson(json);

  Map<String, dynamic> toJson() => _$FixtureTeamToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum FixtureTeamStateEnum {
  @JsonValue(r'invited')
  invited(r'invited'),
  @JsonValue(r'accepted')
  accepted(r'accepted'),
  @JsonValue(r'declined')
  declined(r'declined'),
  @JsonValue(r'external')
  external_(r'external'),
  @JsonValue(r'')
  empty(r''),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const FixtureTeamStateEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
