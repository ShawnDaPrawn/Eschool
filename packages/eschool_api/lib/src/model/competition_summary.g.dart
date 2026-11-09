// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'competition_summary.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CompetitionSummary _$CompetitionSummaryFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'CompetitionSummary',
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
          ],
        );
        final val = CompetitionSummary(
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
              _$CompetitionSummaryMyRoleEnumEnumMap,
              v,
              unknownValue: CompetitionSummaryMyRoleEnum.unknownDefaultOpenApi,
            ),
          ),
          myState: $checkedConvert(
            'my_state',
            (v) => $enumDecode(
              _$CompetitionSummaryMyStateEnumEnumMap,
              v,
              unknownValue: CompetitionSummaryMyStateEnum.unknownDefaultOpenApi,
            ),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'pointsWin': 'points_win',
        'pointsDraw': 'points_draw',
        'pointsLoss': 'points_loss',
        'myRole': 'my_role',
        'myState': 'my_state',
      },
    );

Map<String, dynamic> _$CompetitionSummaryToJson(CompetitionSummary instance) =>
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
      'my_role': _$CompetitionSummaryMyRoleEnumEnumMap[instance.myRole]!,
      'my_state': _$CompetitionSummaryMyStateEnumEnumMap[instance.myState]!,
    };

const _$CompetitionSummaryMyRoleEnumEnumMap = {
  CompetitionSummaryMyRoleEnum.organiser: 'organiser',
  CompetitionSummaryMyRoleEnum.member: 'member',
  CompetitionSummaryMyRoleEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$CompetitionSummaryMyStateEnumEnumMap = {
  CompetitionSummaryMyStateEnum.invited: 'invited',
  CompetitionSummaryMyStateEnum.accepted: 'accepted',
  CompetitionSummaryMyStateEnum.declined: 'declined',
  CompetitionSummaryMyStateEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
