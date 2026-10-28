// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wallet_deposit_receipt_verified_receipt.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WalletDepositReceiptVerifiedReceipt
_$WalletDepositReceiptVerifiedReceiptFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'WalletDepositReceiptVerifiedReceipt',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'reference',
        'currency',
        'amount_cents',
        'method',
        'source_reference',
        'received_on',
        'collected_by',
        'fee_cents',
        'net_cents',
        'settlement_reference',
      ],
    );
    final val = WalletDepositReceiptVerifiedReceipt(
      reference: $checkedConvert('reference', (v) => v as String),
      currency: $checkedConvert('currency', (v) => v as String),
      amountCents: $checkedConvert('amount_cents', (v) => (v as num).toInt()),
      method: $checkedConvert(
        'method',
        (v) => $enumDecode(
          _$WalletDepositReceiptVerifiedReceiptMethodEnumEnumMap,
          v,
          unknownValue: WalletDepositReceiptVerifiedReceiptMethodEnum
              .unknownDefaultOpenApi,
        ),
      ),
      sourceReference: $checkedConvert('source_reference', (v) => v as String),
      receivedOn: $checkedConvert(
        'received_on',
        (v) => DateTime.parse(v as String),
      ),
      collectedBy: $checkedConvert(
        'collected_by',
        (v) => $enumDecode(
          _$WalletDepositReceiptVerifiedReceiptCollectedByEnumEnumMap,
          v,
          unknownValue: WalletDepositReceiptVerifiedReceiptCollectedByEnum
              .unknownDefaultOpenApi,
        ),
      ),
      feeCents: $checkedConvert('fee_cents', (v) => (v as num).toInt()),
      netCents: $checkedConvert('net_cents', (v) => (v as num).toInt()),
      settlementReference: $checkedConvert(
        'settlement_reference',
        (v) => v as String?,
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'amountCents': 'amount_cents',
    'sourceReference': 'source_reference',
    'receivedOn': 'received_on',
    'collectedBy': 'collected_by',
    'feeCents': 'fee_cents',
    'netCents': 'net_cents',
    'settlementReference': 'settlement_reference',
  },
);

Map<String, dynamic> _$WalletDepositReceiptVerifiedReceiptToJson(
  WalletDepositReceiptVerifiedReceipt instance,
) => <String, dynamic>{
  'reference': instance.reference,
  'currency': instance.currency,
  'amount_cents': instance.amountCents,
  'method':
      _$WalletDepositReceiptVerifiedReceiptMethodEnumEnumMap[instance.method]!,
  'source_reference': instance.sourceReference,
  'received_on': instance.receivedOn.toIso8601String(),
  'collected_by':
      _$WalletDepositReceiptVerifiedReceiptCollectedByEnumEnumMap[instance
          .collectedBy]!,
  'fee_cents': instance.feeCents,
  'net_cents': instance.netCents,
  'settlement_reference': instance.settlementReference,
};

const _$WalletDepositReceiptVerifiedReceiptMethodEnumEnumMap = {
  WalletDepositReceiptVerifiedReceiptMethodEnum.cash: 'cash',
  WalletDepositReceiptVerifiedReceiptMethodEnum.eft: 'eft',
  WalletDepositReceiptVerifiedReceiptMethodEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$WalletDepositReceiptVerifiedReceiptCollectedByEnumEnumMap = {
  WalletDepositReceiptVerifiedReceiptCollectedByEnum.school: 'school',
  WalletDepositReceiptVerifiedReceiptCollectedByEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
