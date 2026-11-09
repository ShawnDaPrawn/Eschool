// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'reenrolment_outstanding.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ReenrolmentOutstanding _$ReenrolmentOutstandingFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ReenrolmentOutstanding', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const [
      'contract_id',
      'version',
      'state',
      'revision',
      'audience',
      'confirmed',
      'outstanding',
      'page',
      'items',
    ],
  );
  final val = ReenrolmentOutstanding(
    contractId: $checkedConvert('contract_id', (v) => (v as num).toInt()),
    version: $checkedConvert('version', (v) => (v as num).toInt()),
    state: $checkedConvert('state', (v) => v as String),
    revision: $checkedConvert('revision', (v) => (v as num).toInt()),
    audience: $checkedConvert('audience', (v) => (v as num).toInt()),
    confirmed: $checkedConvert('confirmed', (v) => (v as num).toInt()),
    outstanding: $checkedConvert('outstanding', (v) => (v as num).toInt()),
    page: $checkedConvert('page', (v) => (v as num).toInt()),
    items: $checkedConvert(
      'items',
      (v) => (v as List<dynamic>)
          .map(
            (e) => ReenrolmentOutstandingItemsInner.fromJson(
              e as Map<String, dynamic>,
            ),
          )
          .toList(),
    ),
  );
  return val;
}, fieldKeyMap: const {'contractId': 'contract_id'});

Map<String, dynamic> _$ReenrolmentOutstandingToJson(
  ReenrolmentOutstanding instance,
) => <String, dynamic>{
  'contract_id': instance.contractId,
  'version': instance.version,
  'state': instance.state,
  'revision': instance.revision,
  'audience': instance.audience,
  'confirmed': instance.confirmed,
  'outstanding': instance.outstanding,
  'page': instance.page,
  'items': instance.items.map((e) => e.toJson()).toList(),
};
