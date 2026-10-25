// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'discipline_hearing_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DisciplineHearingWrite _$DisciplineHearingWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'DisciplineHearingWrite',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'operation_id',
        'expected_revision',
        'decision',
        'reason',
      ],
    );
    final val = DisciplineHearingWrite(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      expectedRevision: $checkedConvert(
        'expected_revision',
        (v) => (v as num).toInt(),
      ),
      decision: $checkedConvert(
        'decision',
        (v) => $enumDecode(
          _$DisciplineHearingWriteDecisionEnumEnumMap,
          v,
          unknownValue:
              DisciplineHearingWriteDecisionEnum.unknownDefaultOpenApi,
        ),
      ),
      reason: $checkedConvert('reason', (v) => v as String),
      familyReason: $checkedConvert('family_reason', (v) => v as String?),
      sanctionCode: $checkedConvert('sanction_code', (v) => v as String?),
    );
    return val;
  },
  fieldKeyMap: const {
    'operationId': 'operation_id',
    'expectedRevision': 'expected_revision',
    'familyReason': 'family_reason',
    'sanctionCode': 'sanction_code',
  },
);

Map<String, dynamic> _$DisciplineHearingWriteToJson(
  DisciplineHearingWrite instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'expected_revision': instance.expectedRevision,
  'decision': _$DisciplineHearingWriteDecisionEnumEnumMap[instance.decision]!,
  'reason': instance.reason,
  'family_reason': ?instance.familyReason,
  'sanction_code': ?instance.sanctionCode,
};

const _$DisciplineHearingWriteDecisionEnumEnumMap = {
  DisciplineHearingWriteDecisionEnum.noAction: 'no_action',
  DisciplineHearingWriteDecisionEnum.sanction: 'sanction',
  DisciplineHearingWriteDecisionEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
