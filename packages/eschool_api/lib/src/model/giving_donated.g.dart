// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'giving_donated.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GivingDonated _$GivingDonatedFromJson(Map<String, dynamic> json) =>
    $checkedCreate('GivingDonated', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['id', 'receipt', 'ledger_entry_id'],
      );
      final val = GivingDonated(
        id: $checkedConvert('id', (v) => (v as num).toInt()),
        receipt: $checkedConvert('receipt', (v) => v as String),
        ledgerEntryId: $checkedConvert(
          'ledger_entry_id',
          (v) => (v as num).toInt(),
        ),
      );
      return val;
    }, fieldKeyMap: const {'ledgerEntryId': 'ledger_entry_id'});

Map<String, dynamic> _$GivingDonatedToJson(GivingDonated instance) =>
    <String, dynamic>{
      'id': instance.id,
      'receipt': instance.receipt,
      'ledger_entry_id': instance.ledgerEntryId,
    };
