// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'secure_card_portrait_receipt.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SecureCardPortraitReceipt _$SecureCardPortraitReceiptFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SecureCardPortraitReceipt', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['person_id', 'portrait_id', 'revision'],
  );
  final val = SecureCardPortraitReceipt(
    personId: $checkedConvert('person_id', (v) => (v as num).toInt()),
    portraitId: $checkedConvert('portrait_id', (v) => v as String),
    revision: $checkedConvert('revision', (v) => (v as num).toInt()),
  );
  return val;
}, fieldKeyMap: const {'personId': 'person_id', 'portraitId': 'portrait_id'});

Map<String, dynamic> _$SecureCardPortraitReceiptToJson(
  SecureCardPortraitReceipt instance,
) => <String, dynamic>{
  'person_id': instance.personId,
  'portrait_id': instance.portraitId,
  'revision': instance.revision,
};
