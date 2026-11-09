// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'competition.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

Competition _$CompetitionFromJson(Map<String, dynamic> json) => $checkedCreate(
  'Competition',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'kind',
        'name',
        'discipline',
        'season',
        'points_win',
        'points_draw',
        'points_loss',
        'organiser',
        'my_role',
        'my_state',
        'members',
        'standings',
        'can_invite',
        'can_decide',
      ],
    );
    final val = Competition(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      kind: $checkedConvert('kind', (v) => v as String),
      name: $checkedConvert('name', (v) => v as String),
      discipline: $checkedConvert('discipline', (v) => v as String),
      season: $checkedConvert('season', (v) => v as String),
      pointsWin: $checkedConvert('points_win', (v) => (v as num).toInt()),
      pointsDraw: $checkedConvert('points_draw', (v) => (v as num).toInt()),
      pointsLoss: $checkedConvert('points_loss', (v) => (v as num).toInt()),
      organiser: $checkedConvert('organiser', (v) => v as String),
      myRole: $checkedConvert(
        'my_role',
        (v) => $enumDecode(
          _$CompetitionMyRoleEnumEnumMap,
          v,
          unknownValue: CompetitionMyRoleEnum.unknownDefaultOpenApi,
        ),
      ),
      myState: $checkedConvert(
        'my_state',
        (v) => $enumDecode(
          _$CompetitionMyStateEnumEnumMap,
          v,
          unknownValue: CompetitionMyStateEnum.unknownDefaultOpenApi,
        ),
      ),
      members: $checkedConvert(
        'members',
        (v) => (v as List<dynamic>)
            .map(
              (e) =>
                  CompetitionMembersInner.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
      ),
      standings: $checkedConvert(
        'standings',
        (v) => (v as List<dynamic>)
            .map(
              (e) =>
                  CompetitionStandingsInner.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
      ),
      canInvite: $checkedConvert('can_invite', (v) => v as bool),
      canDecide: $checkedConvert('can_decide', (v) => v as bool),
    );
    return val;
  },
  fieldKeyMap: const {
    'pointsWin': 'points_win',
    'pointsDraw': 'points_draw',
    'pointsLoss': 'points_loss',
    'myRole': 'my_role',
    'myState': 'my_state',
    'canInvite': 'can_invite',
    'canDecide': 'can_decide',
  },
);

Map<String, dynamic> _$CompetitionToJson(Competition instance) =>
    <String, dynamic>{
      'id': instance.id,
      'kind': instance.kind,
      'name': instance.name,
      'discipline': instance.discipline,
      'season': instance.season,
      'points_win': instance.pointsWin,
      'points_draw': instance.pointsDraw,
      'points_loss': instance.pointsLoss,
      'organiser': instance.organiser,
      'my_role': _$CompetitionMyRoleEnumEnumMap[instance.myRole]!,
      'my_state': _$CompetitionMyStateEnumEnumMap[instance.myState]!,
      'members': instance.members.map((e) => e.toJson()).toList(),
      'standings': instance.standings.map((e) => e.toJson()).toList(),
      'can_invite': instance.canInvite,
      'can_decide': instance.canDecide,
    };

const _$CompetitionMyRoleEnumEnumMap = {
  CompetitionMyRoleEnum.organiser: 'organiser',
  CompetitionMyRoleEnum.member: 'member',
  CompetitionMyRoleEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};

const _$CompetitionMyStateEnumEnumMap = {
  CompetitionMyStateEnum.invited: 'invited',
  CompetitionMyStateEnum.accepted: 'accepted',
  CompetitionMyStateEnum.declined: 'declined',
  CompetitionMyStateEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
