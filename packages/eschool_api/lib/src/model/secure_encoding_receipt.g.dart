// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'secure_encoding_receipt.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SecureEncodingReceipt _$SecureEncodingReceiptFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'SecureEncodingReceipt',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'encoded',
        'plan_id',
        'card_id',
        'revision',
        'verified_counter',
        'encoded_at',
        'collection_authorized',
        'sign_in_authorized',
      ],
    );
    final val = SecureEncodingReceipt(
      encoded: $checkedConvert('encoded', (v) => v as bool),
      planId: $checkedConvert('plan_id', (v) => v as String),
      cardId: $checkedConvert('card_id', (v) => v as String),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      verifiedCounter: $checkedConvert(
        'verified_counter',
        (v) => (v as num).toInt(),
      ),
      encodedAt: $checkedConvert(
        'encoded_at',
        (v) => DateTime.parse(v as String),
      ),
      collectionAuthorized: $checkedConvert(
        'collection_authorized',
        (v) => v as bool,
      ),
      signInAuthorized: $checkedConvert('sign_in_authorized', (v) => v as bool),
    );
    return val;
  },
  fieldKeyMap: const {
    'planId': 'plan_id',
    'cardId': 'card_id',
    'verifiedCounter': 'verified_counter',
    'encodedAt': 'encoded_at',
    'collectionAuthorized': 'collection_authorized',
    'signInAuthorized': 'sign_in_authorized',
  },
);

Map<String, dynamic> _$SecureEncodingReceiptToJson(
  SecureEncodingReceipt instance,
) => <String, dynamic>{
  'encoded': instance.encoded,
  'plan_id': instance.planId,
  'card_id': instance.cardId,
  'revision': instance.revision,
  'verified_counter': instance.verifiedCounter,
  'encoded_at': instance.encodedAt.toIso8601String(),
  'collection_authorized': instance.collectionAuthorized,
  'sign_in_authorized': instance.signInAuthorized,
};
