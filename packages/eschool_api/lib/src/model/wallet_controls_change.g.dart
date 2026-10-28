// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wallet_controls_change.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WalletControlsChange _$WalletControlsChangeFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'WalletControlsChange',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'operation_id',
        'revision',
        'daily_spend_cents',
        'all_categories',
        'categories',
      ],
    );
    final val = WalletControlsChange(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      dailySpendCents: $checkedConvert(
        'daily_spend_cents',
        (v) => (v as num).toInt(),
      ),
      allCategories: $checkedConvert('all_categories', (v) => v as bool),
      categories: $checkedConvert(
        'categories',
        (v) => (v as List<dynamic>).map((e) => e as String).toSet(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'operationId': 'operation_id',
    'dailySpendCents': 'daily_spend_cents',
    'allCategories': 'all_categories',
  },
);

Map<String, dynamic> _$WalletControlsChangeToJson(
  WalletControlsChange instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'revision': instance.revision,
  'daily_spend_cents': instance.dailySpendCents,
  'all_categories': instance.allCategories,
  'categories': instance.categories.toList(),
};
