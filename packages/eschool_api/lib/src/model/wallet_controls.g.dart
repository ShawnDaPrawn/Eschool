// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wallet_controls.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WalletControls _$WalletControlsFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'WalletControls',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'revision',
            'daily_spend_cents',
            'effective_daily_spend_cents',
            'all_categories',
            'categories',
            'configured',
          ],
        );
        final val = WalletControls(
          revision: $checkedConvert('revision', (v) => (v as num).toInt()),
          dailySpendCents: $checkedConvert(
            'daily_spend_cents',
            (v) => (v as num?)?.toInt(),
          ),
          effectiveDailySpendCents: $checkedConvert(
            'effective_daily_spend_cents',
            (v) => (v as num).toInt(),
          ),
          allCategories: $checkedConvert('all_categories', (v) => v as bool),
          categories: $checkedConvert(
            'categories',
            (v) => (v as List<dynamic>).map((e) => e as String).toList(),
          ),
          configured: $checkedConvert('configured', (v) => v as bool),
        );
        return val;
      },
      fieldKeyMap: const {
        'dailySpendCents': 'daily_spend_cents',
        'effectiveDailySpendCents': 'effective_daily_spend_cents',
        'allCategories': 'all_categories',
      },
    );

Map<String, dynamic> _$WalletControlsToJson(WalletControls instance) =>
    <String, dynamic>{
      'revision': instance.revision,
      'daily_spend_cents': instance.dailySpendCents,
      'effective_daily_spend_cents': instance.effectiveDailySpendCents,
      'all_categories': instance.allCategories,
      'categories': instance.categories,
      'configured': instance.configured,
    };
