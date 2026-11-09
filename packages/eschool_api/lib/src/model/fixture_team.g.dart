// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'fixture_team.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FixtureTeam _$FixtureTeamFromJson(Map<String, dynamic> json) =>
    $checkedCreate('FixtureTeam', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['name', 'external', 'state']);
      final val = FixtureTeam(
        name: $checkedConvert('name', (v) => v as String),
        external_: $checkedConvert('external', (v) => v as bool),
        state: $checkedConvert(
          'state',
          (v) => $enumDecode(
            _$FixtureTeamStateEnumEnumMap,
            v,
            unknownValue: FixtureTeamStateEnum.unknownDefaultOpenApi,
          ),
        ),
        teamLabel: $checkedConvert('team_label', (v) => v as String?),
      );
      return val;
    }, fieldKeyMap: const {'external_': 'external', 'teamLabel': 'team_label'});

Map<String, dynamic> _$FixtureTeamToJson(FixtureTeam instance) =>
    <String, dynamic>{
      'name': instance.name,
      'external': instance.external_,
      'state': _$FixtureTeamStateEnumEnumMap[instance.state]!,
      'team_label': ?instance.teamLabel,
    };

const _$FixtureTeamStateEnumEnumMap = {
  FixtureTeamStateEnum.invited: 'invited',
  FixtureTeamStateEnum.accepted: 'accepted',
  FixtureTeamStateEnum.declined: 'declined',
  FixtureTeamStateEnum.external_: 'external',
  FixtureTeamStateEnum.empty: '',
  FixtureTeamStateEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
