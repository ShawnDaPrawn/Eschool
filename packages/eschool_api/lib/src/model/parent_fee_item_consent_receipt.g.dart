// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'parent_fee_item_consent_receipt.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ParentFeeItemConsentReceipt _$ParentFeeItemConsentReceiptFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'ParentFeeItemConsentReceipt',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'school_id',
        'holder_user_id',
        'fee_account_id',
        'fee_item_id',
        'session_year_id',
        'learner_id',
        'revision',
        'active',
        'recorded_at',
      ],
    );
    final val = ParentFeeItemConsentReceipt(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
      holderUserId: $checkedConvert(
        'holder_user_id',
        (v) => (v as num).toInt(),
      ),
      feeAccountId: $checkedConvert(
        'fee_account_id',
        (v) => (v as num).toInt(),
      ),
      feeItemId: $checkedConvert('fee_item_id', (v) => (v as num).toInt()),
      sessionYearId: $checkedConvert(
        'session_year_id',
        (v) => (v as num).toInt(),
      ),
      learnerId: $checkedConvert('learner_id', (v) => (v as num?)?.toInt()),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      active: $checkedConvert('active', (v) => v as bool),
      recordedAt: $checkedConvert(
        'recorded_at',
        (v) => DateTime.parse(v as String),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'schoolId': 'school_id',
    'holderUserId': 'holder_user_id',
    'feeAccountId': 'fee_account_id',
    'feeItemId': 'fee_item_id',
    'sessionYearId': 'session_year_id',
    'learnerId': 'learner_id',
    'recordedAt': 'recorded_at',
  },
);

Map<String, dynamic> _$ParentFeeItemConsentReceiptToJson(
  ParentFeeItemConsentReceipt instance,
) => <String, dynamic>{
  'id': instance.id,
  'school_id': instance.schoolId,
  'holder_user_id': instance.holderUserId,
  'fee_account_id': instance.feeAccountId,
  'fee_item_id': instance.feeItemId,
  'session_year_id': instance.sessionYearId,
  'learner_id': instance.learnerId,
  'revision': instance.revision,
  'active': instance.active,
  'recorded_at': instance.recordedAt.toIso8601String(),
};
