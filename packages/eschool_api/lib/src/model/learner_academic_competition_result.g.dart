// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'learner_academic_competition_result.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

LearnerAcademicCompetitionResult _$LearnerAcademicCompetitionResultFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('LearnerAcademicCompetitionResult', json, (
  $checkedConvert,
) {
  $checkKeys(
    json,
    requiredKeys: const ['state', 'our_score', 'their_score', 'outcome'],
  );
  final val = LearnerAcademicCompetitionResult(
    state: $checkedConvert(
      'state',
      (v) => $enumDecode(
        _$LearnerAcademicCompetitionResultStateEnumEnumMap,
        v,
        unknownValue:
            LearnerAcademicCompetitionResultStateEnum.unknownDefaultOpenApi,
      ),
    ),
    ourScore: $checkedConvert('our_score', (v) => (v as num?)?.toInt()),
    theirScore: $checkedConvert('their_score', (v) => (v as num?)?.toInt()),
    outcome: $checkedConvert(
      'outcome',
      (v) => $enumDecodeNullable(
        _$LearnerAcademicCompetitionResultOutcomeEnumEnumMap,
        v,
        unknownValue:
            LearnerAcademicCompetitionResultOutcomeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
}, fieldKeyMap: const {'ourScore': 'our_score', 'theirScore': 'their_score'});

Map<String, dynamic> _$LearnerAcademicCompetitionResultToJson(
  LearnerAcademicCompetitionResult instance,
) => <String, dynamic>{
  'state': _$LearnerAcademicCompetitionResultStateEnumEnumMap[instance.state]!,
  'our_score': instance.ourScore,
  'their_score': instance.theirScore,
  'outcome':
      _$LearnerAcademicCompetitionResultOutcomeEnumEnumMap[instance.outcome],
};

const _$LearnerAcademicCompetitionResultStateEnumEnumMap = {
  LearnerAcademicCompetitionResultStateEnum.confirmed: 'confirmed',
  LearnerAcademicCompetitionResultStateEnum.pending: 'pending',
  LearnerAcademicCompetitionResultStateEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$LearnerAcademicCompetitionResultOutcomeEnumEnumMap = {
  LearnerAcademicCompetitionResultOutcomeEnum.won: 'won',
  LearnerAcademicCompetitionResultOutcomeEnum.lost: 'lost',
  LearnerAcademicCompetitionResultOutcomeEnum.draw: 'draw',
  LearnerAcademicCompetitionResultOutcomeEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
