// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'vendor_operator_counter.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

VendorOperatorCounter _$VendorOperatorCounterFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('VendorOperatorCounter', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['id', 'name', 'currency', 'can_collect'],
  );
  final val = VendorOperatorCounter(
    id: $checkedConvert('id', (v) => (v as num).toInt()),
    name: $checkedConvert('name', (v) => v as String),
    currency: $checkedConvert('currency', (v) => v as String),
    canCollect: $checkedConvert('can_collect', (v) => v as bool),
  );
  return val;
}, fieldKeyMap: const {'canCollect': 'can_collect'});

Map<String, dynamic> _$VendorOperatorCounterToJson(
  VendorOperatorCounter instance,
) => <String, dynamic>{
  'id': instance.id,
  'name': instance.name,
  'currency': instance.currency,
  'can_collect': instance.canCollect,
};
