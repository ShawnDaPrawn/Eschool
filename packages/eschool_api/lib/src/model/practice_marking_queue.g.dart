// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'practice_marking_queue.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PracticeMarkingQueue _$PracticeMarkingQueueFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('PracticeMarkingQueue', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['group_id', 'items', 'page', 'last_page', 'total'],
  );
  final val = PracticeMarkingQueue(
    groupId: $checkedConvert('group_id', (v) => (v as num).toInt()),
    items: $checkedConvert(
      'items',
      (v) => (v as List<dynamic>)
          .map((e) => PracticeMarkingItem.fromJson(e as Map<String, dynamic>))
          .toList(),
    ),
    page: $checkedConvert('page', (v) => (v as num).toInt()),
    lastPage: $checkedConvert('last_page', (v) => (v as num).toInt()),
    total: $checkedConvert('total', (v) => (v as num).toInt()),
  );
  return val;
}, fieldKeyMap: const {'groupId': 'group_id', 'lastPage': 'last_page'});

Map<String, dynamic> _$PracticeMarkingQueueToJson(
  PracticeMarkingQueue instance,
) => <String, dynamic>{
  'group_id': instance.groupId,
  'items': instance.items.map((e) => e.toJson()).toList(),
  'page': instance.page,
  'last_page': instance.lastPage,
  'total': instance.total,
};
