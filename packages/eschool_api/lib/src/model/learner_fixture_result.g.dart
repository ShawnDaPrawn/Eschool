// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'learner_fixture_result.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

LearnerFixtureResult _$LearnerFixtureResultFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('LearnerFixtureResult', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['state', 'our_score', 'their_score']);
  final val = LearnerFixtureResult(
    state: $checkedConvert(
      'state',
      (v) => $enumDecode(
        _$LearnerFixtureResultStateEnumEnumMap,
        v,
        unknownValue: LearnerFixtureResultStateEnum.unknownDefaultOpenApi,
      ),
    ),
    ourScore: $checkedConvert('our_score', (v) => (v as num?)?.toInt()),
    theirScore: $checkedConvert('their_score', (v) => (v as num?)?.toInt()),
  );
  return val;
}, fieldKeyMap: const {'ourScore': 'our_score', 'theirScore': 'their_score'});

Map<String, dynamic> _$LearnerFixtureResultToJson(
  LearnerFixtureResult instance,
) => <String, dynamic>{
  'state': _$LearnerFixtureResultStateEnumEnumMap[instance.state]!,
  'our_score': instance.ourScore,
  'their_score': instance.theirScore,
};

const _$LearnerFixtureResultStateEnumEnumMap = {
  LearnerFixtureResultStateEnum.confirmed: 'confirmed',
  LearnerFixtureResultStateEnum.pending: 'pending',
  LearnerFixtureResultStateEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
