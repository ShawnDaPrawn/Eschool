// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wallet_deposit_claim_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WalletDepositClaimWrite _$WalletDepositClaimWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'WalletDepositClaimWrite',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'operation_id',
        'amount',
        'method',
        'source_reference',
        'paid_on',
        'evidence',
        'confirmed',
      ],
    );
    final val = WalletDepositClaimWrite(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      amount: $checkedConvert('amount', (v) => v as String),
      method: $checkedConvert(
        'method',
        (v) => $enumDecode(
          _$WalletDepositClaimWriteMethodEnumEnumMap,
          v,
          unknownValue: WalletDepositClaimWriteMethodEnum.unknownDefaultOpenApi,
        ),
      ),
      sourceReference: $checkedConvert('source_reference', (v) => v as String),
      paidOn: $checkedConvert('paid_on', (v) => DateTime.parse(v as String)),
      evidence: $checkedConvert('evidence', (v) => v as String),
      confirmed: $checkedConvert('confirmed', (v) => v as bool),
    );
    return val;
  },
  fieldKeyMap: const {
    'operationId': 'operation_id',
    'sourceReference': 'source_reference',
    'paidOn': 'paid_on',
  },
);

Map<String, dynamic> _$WalletDepositClaimWriteToJson(
  WalletDepositClaimWrite instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'amount': instance.amount,
  'method': _$WalletDepositClaimWriteMethodEnumEnumMap[instance.method]!,
  'source_reference': instance.sourceReference,
  'paid_on': instance.paidOn.toIso8601String(),
  'evidence': instance.evidence,
  'confirmed': instance.confirmed,
};

const _$WalletDepositClaimWriteMethodEnumEnumMap = {
  WalletDepositClaimWriteMethodEnum.cash: 'cash',
  WalletDepositClaimWriteMethodEnum.eft: 'eft',
  WalletDepositClaimWriteMethodEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
