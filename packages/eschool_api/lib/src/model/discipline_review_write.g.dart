// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'discipline_review_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DisciplineReviewWrite _$DisciplineReviewWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'DisciplineReviewWrite',
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
    final val = DisciplineReviewWrite(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      expectedRevision: $checkedConvert(
        'expected_revision',
        (v) => (v as num).toInt(),
      ),
      decision: $checkedConvert(
        'decision',
        (v) => $enumDecode(
          _$DisciplineReviewWriteDecisionEnumEnumMap,
          v,
          unknownValue: DisciplineReviewWriteDecisionEnum.unknownDefaultOpenApi,
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

Map<String, dynamic> _$DisciplineReviewWriteToJson(
  DisciplineReviewWrite instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'expected_revision': instance.expectedRevision,
  'decision': _$DisciplineReviewWriteDecisionEnumEnumMap[instance.decision]!,
  'reason': instance.reason,
  'family_reason': ?instance.familyReason,
  'sanction_code': ?instance.sanctionCode,
};

const _$DisciplineReviewWriteDecisionEnumEnumMap = {
  DisciplineReviewWriteDecisionEnum.confirm: 'confirm',
  DisciplineReviewWriteDecisionEnum.overturn: 'overturn',
  DisciplineReviewWriteDecisionEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
