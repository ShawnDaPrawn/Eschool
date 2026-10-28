// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wallet_statement_entry.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WalletStatementEntry _$WalletStatementEntryFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'WalletStatementEntry',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'entry_id',
        'posted_at',
        'change_cents',
        'balance_after_cents',
        'description',
      ],
    );
    final val = WalletStatementEntry(
      entryId: $checkedConvert('entry_id', (v) => (v as num).toInt()),
      postedAt: $checkedConvert(
        'posted_at',
        (v) => DateTime.parse(v as String),
      ),
      changeCents: $checkedConvert('change_cents', (v) => (v as num).toInt()),
      balanceAfterCents: $checkedConvert(
        'balance_after_cents',
        (v) => (v as num).toInt(),
      ),
      description: $checkedConvert('description', (v) => v as String),
    );
    return val;
  },
  fieldKeyMap: const {
    'entryId': 'entry_id',
    'postedAt': 'posted_at',
    'changeCents': 'change_cents',
    'balanceAfterCents': 'balance_after_cents',
  },
);

Map<String, dynamic> _$WalletStatementEntryToJson(
  WalletStatementEntry instance,
) => <String, dynamic>{
  'entry_id': instance.entryId,
  'posted_at': instance.postedAt.toIso8601String(),
  'change_cents': instance.changeCents,
  'balance_after_cents': instance.balanceAfterCents,
  'description': instance.description,
};
