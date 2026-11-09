// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'competition_decision.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CompetitionDecision _$CompetitionDecisionFromJson(Map<String, dynamic> json) =>
    $checkedCreate('CompetitionDecision', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['operation_id', 'decision']);
      final val = CompetitionDecision(
        operationId: $checkedConvert('operation_id', (v) => v as String),
        decision: $checkedConvert(
          'decision',
          (v) => $enumDecode(
            _$CompetitionDecisionDecisionEnumEnumMap,
            v,
            unknownValue: CompetitionDecisionDecisionEnum.unknownDefaultOpenApi,
          ),
        ),
      );
      return val;
    }, fieldKeyMap: const {'operationId': 'operation_id'});

Map<String, dynamic> _$CompetitionDecisionToJson(
  CompetitionDecision instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'decision': _$CompetitionDecisionDecisionEnumEnumMap[instance.decision]!,
};

const _$CompetitionDecisionDecisionEnumEnumMap = {
  CompetitionDecisionDecisionEnum.accept: 'accept',
  CompetitionDecisionDecisionEnum.decline: 'decline',
  CompetitionDecisionDecisionEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
