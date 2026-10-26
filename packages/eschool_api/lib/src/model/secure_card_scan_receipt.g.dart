// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'secure_card_scan_receipt.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SecureCardScanReceipt _$SecureCardScanReceiptFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'SecureCardScanReceipt',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'scan_id',
        'card_id',
        'person_id',
        'type',
        'outcome',
        'scanned_at',
      ],
    );
    final val = SecureCardScanReceipt(
      scanId: $checkedConvert('scan_id', (v) => v as String),
      cardId: $checkedConvert('card_id', (v) => v as String),
      personId: $checkedConvert('person_id', (v) => (v as num).toInt()),
      type: $checkedConvert('type', (v) => v as String),
      outcome: $checkedConvert('outcome', (v) => v as String),
      scannedAt: $checkedConvert(
        'scanned_at',
        (v) => DateTime.parse(v as String),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'scanId': 'scan_id',
    'cardId': 'card_id',
    'personId': 'person_id',
    'scannedAt': 'scanned_at',
  },
);

Map<String, dynamic> _$SecureCardScanReceiptToJson(
  SecureCardScanReceipt instance,
) => <String, dynamic>{
  'scan_id': instance.scanId,
  'card_id': instance.cardId,
  'person_id': instance.personId,
  'type': instance.type,
  'outcome': instance.outcome,
  'scanned_at': instance.scannedAt.toIso8601String(),
};
