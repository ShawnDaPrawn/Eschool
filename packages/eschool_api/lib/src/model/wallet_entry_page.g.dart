// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wallet_entry_page.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WalletEntryPage _$WalletEntryPageFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('WalletEntryPage', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['items', 'current_page', 'per_page', 'total'],
  );
  final val = WalletEntryPage(
    items: $checkedConvert(
      'items',
      (v) => (v as List<dynamic>)
          .map((e) => WalletStatementEntry.fromJson(e as Map<String, dynamic>))
          .toList(),
    ),
    currentPage: $checkedConvert('current_page', (v) => (v as num).toInt()),
    perPage: $checkedConvert('per_page', (v) => (v as num).toInt()),
    total: $checkedConvert('total', (v) => (v as num).toInt()),
  );
  return val;
}, fieldKeyMap: const {'currentPage': 'current_page', 'perPage': 'per_page'});

Map<String, dynamic> _$WalletEntryPageToJson(WalletEntryPage instance) =>
    <String, dynamic>{
      'items': instance.items.map((e) => e.toJson()).toList(),
      'current_page': instance.currentPage,
      'per_page': instance.perPage,
      'total': instance.total,
    };
