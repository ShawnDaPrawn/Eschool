// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'reenrolment_outstanding_items_inner_awaiting_inner.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ReenrolmentOutstandingItemsInnerAwaitingInner
_$ReenrolmentOutstandingItemsInnerAwaitingInnerFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ReenrolmentOutstandingItemsInnerAwaitingInner', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['user_id', 'name']);
  final val = ReenrolmentOutstandingItemsInnerAwaitingInner(
    userId: $checkedConvert('user_id', (v) => (v as num).toInt()),
    name: $checkedConvert('name', (v) => v as String),
  );
  return val;
}, fieldKeyMap: const {'userId': 'user_id'});

Map<String, dynamic> _$ReenrolmentOutstandingItemsInnerAwaitingInnerToJson(
  ReenrolmentOutstandingItemsInnerAwaitingInner instance,
) => <String, dynamic>{'user_id': instance.userId, 'name': instance.name};
