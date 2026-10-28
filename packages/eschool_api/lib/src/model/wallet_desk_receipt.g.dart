// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wallet_desk_receipt.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WalletDeskReceipt _$WalletDeskReceiptFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'WalletDeskReceipt',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'reference',
        'currency',
        'amount_cents',
        'status',
        'created_at',
        'processed_at',
        'collected_by',
        'fee_cents',
        'net_cents',
      ],
    );
    final val = WalletDeskReceipt(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      reference: $checkedConvert('reference', (v) => v as String),
      currency: $checkedConvert('currency', (v) => v as String),
      amountCents: $checkedConvert('amount_cents', (v) => (v as num).toInt()),
      status: $checkedConvert(
        'status',
        (v) => $enumDecode(
          _$WalletDeskReceiptStatusEnumEnumMap,
          v,
          unknownValue: WalletDeskReceiptStatusEnum.unknownDefaultOpenApi,
        ),
      ),
      createdAt: $checkedConvert(
        'created_at',
        (v) => DateTime.parse(v as String),
      ),
      processedAt: $checkedConvert(
        'processed_at',
        (v) => v == null ? null : DateTime.parse(v as String),
      ),
      collectedBy: $checkedConvert(
        'collected_by',
        (v) => $enumDecode(
          _$WalletDeskReceiptCollectedByEnumEnumMap,
          v,
          unknownValue: WalletDeskReceiptCollectedByEnum.unknownDefaultOpenApi,
        ),
      ),
      feeCents: $checkedConvert(
        'fee_cents',
        (v) => $enumDecode(
          _$WalletDeskReceiptFeeCentsEnumEnumMap,
          v,
          unknownValue: WalletDeskReceiptFeeCentsEnum.unknownDefaultOpenApi,
        ),
      ),
      netCents: $checkedConvert('net_cents', (v) => (v as num).toInt()),
    );
    return val;
  },
  fieldKeyMap: const {
    'amountCents': 'amount_cents',
    'createdAt': 'created_at',
    'processedAt': 'processed_at',
    'collectedBy': 'collected_by',
    'feeCents': 'fee_cents',
    'netCents': 'net_cents',
  },
);

Map<String, dynamic> _$WalletDeskReceiptToJson(WalletDeskReceipt instance) =>
    <String, dynamic>{
      'id': instance.id,
      'reference': instance.reference,
      'currency': instance.currency,
      'amount_cents': instance.amountCents,
      'status': _$WalletDeskReceiptStatusEnumEnumMap[instance.status]!,
      'created_at': instance.createdAt.toIso8601String(),
      'processed_at': instance.processedAt?.toIso8601String(),
      'collected_by':
          _$WalletDeskReceiptCollectedByEnumEnumMap[instance.collectedBy]!,
      'fee_cents': _$WalletDeskReceiptFeeCentsEnumEnumMap[instance.feeCents]!,
      'net_cents': instance.netCents,
    };

const _$WalletDeskReceiptStatusEnumEnumMap = {
  WalletDeskReceiptStatusEnum.pending: 'pending',
  WalletDeskReceiptStatusEnum.released: 'released',
  WalletDeskReceiptStatusEnum.cancelled: 'cancelled',
  WalletDeskReceiptStatusEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};

const _$WalletDeskReceiptCollectedByEnumEnumMap = {
  WalletDeskReceiptCollectedByEnum.school: 'school',
  WalletDeskReceiptCollectedByEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$WalletDeskReceiptFeeCentsEnumEnumMap = {
  WalletDeskReceiptFeeCentsEnum.number0: 0,
  WalletDeskReceiptFeeCentsEnum.unknownDefaultOpenApi: 11184809,
};
