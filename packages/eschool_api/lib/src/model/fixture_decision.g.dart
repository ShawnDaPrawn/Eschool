// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'fixture_decision.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FixtureDecision _$FixtureDecisionFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'FixtureDecision',
      json,
      ($checkedConvert) {
        $checkKeys(json, requiredKeys: const ['operation_id', 'decision']);
        final val = FixtureDecision(
          operationId: $checkedConvert('operation_id', (v) => v as String),
          decision: $checkedConvert(
            'decision',
            (v) => $enumDecode(
              _$FixtureDecisionDecisionEnumEnumMap,
              v,
              unknownValue: FixtureDecisionDecisionEnum.unknownDefaultOpenApi,
            ),
          ),
          activityGroupId: $checkedConvert(
            'activity_group_id',
            (v) => (v as num?)?.toInt(),
          ),
          teamLabel: $checkedConvert('team_label', (v) => v as String?),
        );
        return val;
      },
      fieldKeyMap: const {
        'operationId': 'operation_id',
        'activityGroupId': 'activity_group_id',
        'teamLabel': 'team_label',
      },
    );

Map<String, dynamic> _$FixtureDecisionToJson(FixtureDecision instance) =>
    <String, dynamic>{
      'operation_id': instance.operationId,
      'decision': _$FixtureDecisionDecisionEnumEnumMap[instance.decision]!,
      'activity_group_id': ?instance.activityGroupId,
      'team_label': ?instance.teamLabel,
    };

const _$FixtureDecisionDecisionEnumEnumMap = {
  FixtureDecisionDecisionEnum.accept: 'accept',
  FixtureDecisionDecisionEnum.decline: 'decline',
  FixtureDecisionDecisionEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
