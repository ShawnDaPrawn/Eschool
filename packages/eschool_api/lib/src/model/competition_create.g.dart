// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'competition_create.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CompetitionCreate _$CompetitionCreateFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'CompetitionCreate',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'operation_id',
            'name',
            'kind',
            'discipline',
            'season',
            'points_win',
            'points_draw',
            'points_loss',
          ],
        );
        final val = CompetitionCreate(
          operationId: $checkedConvert('operation_id', (v) => v as String),
          name: $checkedConvert('name', (v) => v as String),
          kind: $checkedConvert(
            'kind',
            (v) => $enumDecode(
              _$CompetitionCreateKindEnumEnumMap,
              v,
              unknownValue: CompetitionCreateKindEnum.unknownDefaultOpenApi,
            ),
          ),
          discipline: $checkedConvert('discipline', (v) => v as String),
          season: $checkedConvert('season', (v) => v as String),
          pointsWin: $checkedConvert('points_win', (v) => (v as num).toInt()),
          pointsDraw: $checkedConvert('points_draw', (v) => (v as num).toInt()),
          pointsLoss: $checkedConvert('points_loss', (v) => (v as num).toInt()),
        );
        return val;
      },
      fieldKeyMap: const {
        'operationId': 'operation_id',
        'pointsWin': 'points_win',
        'pointsDraw': 'points_draw',
        'pointsLoss': 'points_loss',
      },
    );

Map<String, dynamic> _$CompetitionCreateToJson(CompetitionCreate instance) =>
    <String, dynamic>{
      'operation_id': instance.operationId,
      'name': instance.name,
      'kind': _$CompetitionCreateKindEnumEnumMap[instance.kind]!,
      'discipline': instance.discipline,
      'season': instance.season,
      'points_win': instance.pointsWin,
      'points_draw': instance.pointsDraw,
      'points_loss': instance.pointsLoss,
    };

const _$CompetitionCreateKindEnumEnumMap = {
  CompetitionCreateKindEnum.sport: 'sport',
  CompetitionCreateKindEnum.debate: 'debate',
  CompetitionCreateKindEnum.quiz: 'quiz',
  CompetitionCreateKindEnum.speech: 'speech',
  CompetitionCreateKindEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
