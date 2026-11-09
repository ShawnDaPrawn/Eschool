// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'decide_bursary_application_request.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DecideBursaryApplicationRequest _$DecideBursaryApplicationRequestFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'DecideBursaryApplicationRequest',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const ['operation_id', 'revision', 'outcome', 'reasons'],
    );
    final val = DecideBursaryApplicationRequest(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      outcome: $checkedConvert(
        'outcome',
        (v) => $enumDecode(
          _$DecideBursaryApplicationRequestOutcomeEnumEnumMap,
          v,
          unknownValue:
              DecideBursaryApplicationRequestOutcomeEnum.unknownDefaultOpenApi,
        ),
      ),
      reasons: $checkedConvert('reasons', (v) => v as String),
      awardKind: $checkedConvert(
        'award_kind',
        (v) => $enumDecodeNullable(
          _$DecideBursaryApplicationRequestAwardKindEnumEnumMap,
          v,
          unknownValue: DecideBursaryApplicationRequestAwardKindEnum
              .unknownDefaultOpenApi,
        ),
      ),
      awardValue: $checkedConvert('award_value', (v) => (v as num?)?.toInt()),
      currency: $checkedConvert('currency', (v) => v as String?),
    );
    return val;
  },
  fieldKeyMap: const {
    'operationId': 'operation_id',
    'awardKind': 'award_kind',
    'awardValue': 'award_value',
  },
);

Map<String, dynamic> _$DecideBursaryApplicationRequestToJson(
  DecideBursaryApplicationRequest instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'revision': instance.revision,
  'outcome':
      _$DecideBursaryApplicationRequestOutcomeEnumEnumMap[instance.outcome]!,
  'reasons': instance.reasons,
  'award_kind':
      ?_$DecideBursaryApplicationRequestAwardKindEnumEnumMap[instance
          .awardKind],
  'award_value': ?instance.awardValue,
  'currency': ?instance.currency,
};

const _$DecideBursaryApplicationRequestOutcomeEnumEnumMap = {
  DecideBursaryApplicationRequestOutcomeEnum.award: 'award',
  DecideBursaryApplicationRequestOutcomeEnum.decline: 'decline',
  DecideBursaryApplicationRequestOutcomeEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$DecideBursaryApplicationRequestAwardKindEnumEnumMap = {
  DecideBursaryApplicationRequestAwardKindEnum.fixed: 'fixed',
  DecideBursaryApplicationRequestAwardKindEnum.percent: 'percent',
  DecideBursaryApplicationRequestAwardKindEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
