// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'parent_fee_receipt.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ParentFeeReceipt _$ParentFeeReceiptFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'ParentFeeReceipt',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'number',
        'pinned_learner_id',
        'method',
        'received_on',
        'currency',
        'amount_cents',
        'allocated_cents',
        'credit_cents',
      ],
    );
    final val = ParentFeeReceipt(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      number: $checkedConvert('number', (v) => v as String),
      pinnedLearnerId: $checkedConvert(
        'pinned_learner_id',
        (v) => (v as num?)?.toInt(),
      ),
      method: $checkedConvert(
        'method',
        (v) => $enumDecode(
          _$ParentFeeReceiptMethodEnumEnumMap,
          v,
          unknownValue: ParentFeeReceiptMethodEnum.unknownDefaultOpenApi,
        ),
      ),
      receivedOn: $checkedConvert('received_on', (v) => v as String),
      currency: $checkedConvert('currency', (v) => v as String),
      amountCents: $checkedConvert('amount_cents', (v) => (v as num).toInt()),
      allocatedCents: $checkedConvert(
        'allocated_cents',
        (v) => (v as num).toInt(),
      ),
      creditCents: $checkedConvert('credit_cents', (v) => (v as num).toInt()),
    );
    return val;
  },
  fieldKeyMap: const {
    'pinnedLearnerId': 'pinned_learner_id',
    'receivedOn': 'received_on',
    'amountCents': 'amount_cents',
    'allocatedCents': 'allocated_cents',
    'creditCents': 'credit_cents',
  },
);

Map<String, dynamic> _$ParentFeeReceiptToJson(ParentFeeReceipt instance) =>
    <String, dynamic>{
      'id': instance.id,
      'number': instance.number,
      'pinned_learner_id': instance.pinnedLearnerId,
      'method': _$ParentFeeReceiptMethodEnumEnumMap[instance.method]!,
      'received_on': instance.receivedOn,
      'currency': instance.currency,
      'amount_cents': instance.amountCents,
      'allocated_cents': instance.allocatedCents,
      'credit_cents': instance.creditCents,
    };

const _$ParentFeeReceiptMethodEnumEnumMap = {
  ParentFeeReceiptMethodEnum.cash: 'cash',
  ParentFeeReceiptMethodEnum.eft: 'eft',
  ParentFeeReceiptMethodEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
