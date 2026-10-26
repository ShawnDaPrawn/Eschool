// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'secure_offline_receipt.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SecureOfflineReceipt _$SecureOfflineReceiptFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'SecureOfflineReceipt',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'scan_id',
        'card_id',
        'outcome',
        'collection_authorized',
        'sign_in_authorized',
      ],
    );
    final val = SecureOfflineReceipt(
      scanId: $checkedConvert('scan_id', (v) => v as String),
      cardId: $checkedConvert('card_id', (v) => v as String),
      outcome: $checkedConvert('outcome', (v) => v as String),
      collectionAuthorized: $checkedConvert(
        'collection_authorized',
        (v) => v as bool,
      ),
      signInAuthorized: $checkedConvert('sign_in_authorized', (v) => v as bool),
    );
    return val;
  },
  fieldKeyMap: const {
    'scanId': 'scan_id',
    'cardId': 'card_id',
    'collectionAuthorized': 'collection_authorized',
    'signInAuthorized': 'sign_in_authorized',
  },
);

Map<String, dynamic> _$SecureOfflineReceiptToJson(
  SecureOfflineReceipt instance,
) => <String, dynamic>{
  'scan_id': instance.scanId,
  'card_id': instance.cardId,
  'outcome': instance.outcome,
  'collection_authorized': instance.collectionAuthorized,
  'sign_in_authorized': instance.signInAuthorized,
};
