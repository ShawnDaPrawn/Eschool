// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'parent_fee_charge_page.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ParentFeeChargePage _$ParentFeeChargePageFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ParentFeeChargePage', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['items', 'current_page', 'per_page', 'total'],
  );
  final val = ParentFeeChargePage(
    items: $checkedConvert(
      'items',
      (v) => (v as List<dynamic>)
          .map((e) => ParentFeeCharge.fromJson(e as Map<String, dynamic>))
          .toList(),
    ),
    currentPage: $checkedConvert('current_page', (v) => (v as num).toInt()),
    perPage: $checkedConvert('per_page', (v) => (v as num).toInt()),
    total: $checkedConvert('total', (v) => (v as num).toInt()),
  );
  return val;
}, fieldKeyMap: const {'currentPage': 'current_page', 'perPage': 'per_page'});

Map<String, dynamic> _$ParentFeeChargePageToJson(
  ParentFeeChargePage instance,
) => <String, dynamic>{
  'items': instance.items.map((e) => e.toJson()).toList(),
  'current_page': instance.currentPage,
  'per_page': instance.perPage,
  'total': instance.total,
};
