// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'parent_fee_item_consent_change.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ParentFeeItemConsentChange _$ParentFeeItemConsentChangeFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'ParentFeeItemConsentChange',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'operation_id',
        'session_year_id',
        'revision',
        'active',
        'confirmed',
      ],
    );
    final val = ParentFeeItemConsentChange(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      sessionYearId: $checkedConvert(
        'session_year_id',
        (v) => (v as num).toInt(),
      ),
      learnerId: $checkedConvert('learner_id', (v) => (v as num?)?.toInt()),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      active: $checkedConvert('active', (v) => v as bool),
      fingerprint: $checkedConvert('fingerprint', (v) => v as String?),
      confirmed: $checkedConvert('confirmed', (v) => v as bool),
    );
    return val;
  },
  fieldKeyMap: const {
    'operationId': 'operation_id',
    'sessionYearId': 'session_year_id',
    'learnerId': 'learner_id',
  },
);

Map<String, dynamic> _$ParentFeeItemConsentChangeToJson(
  ParentFeeItemConsentChange instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'session_year_id': instance.sessionYearId,
  'learner_id': ?instance.learnerId,
  'revision': instance.revision,
  'active': instance.active,
  'fingerprint': ?instance.fingerprint,
  'confirmed': instance.confirmed,
};
