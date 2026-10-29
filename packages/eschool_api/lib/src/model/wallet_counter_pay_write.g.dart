// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wallet_counter_pay_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WalletCounterPayWrite _$WalletCounterPayWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'WalletCounterPayWrite',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const ['operation_id', 'intent_id', 'confirmed'],
    );
    final val = WalletCounterPayWrite(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      intentId: $checkedConvert('intent_id', (v) => v as String),
      qrToken: $checkedConvert('qr_token', (v) => v as String?),
      portraitRevision: $checkedConvert(
        'portrait_revision',
        (v) => (v as num?)?.toInt(),
      ),
      portraitConfirmed: $checkedConvert(
        'portrait_confirmed',
        (v) => v as bool?,
      ),
      confirmed: $checkedConvert('confirmed', (v) => v as bool),
    );
    return val;
  },
  fieldKeyMap: const {
    'operationId': 'operation_id',
    'intentId': 'intent_id',
    'qrToken': 'qr_token',
    'portraitRevision': 'portrait_revision',
    'portraitConfirmed': 'portrait_confirmed',
  },
);

Map<String, dynamic> _$WalletCounterPayWriteToJson(
  WalletCounterPayWrite instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'intent_id': instance.intentId,
  'qr_token': ?instance.qrToken,
  'portrait_revision': ?instance.portraitRevision,
  'portrait_confirmed': ?instance.portraitConfirmed,
  'confirmed': instance.confirmed,
};
