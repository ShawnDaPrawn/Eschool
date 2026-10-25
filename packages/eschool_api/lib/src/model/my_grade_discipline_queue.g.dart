// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'my_grade_discipline_queue.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

MyGradeDisciplineQueue _$MyGradeDisciplineQueueFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('MyGradeDisciplineQueue', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['available', 'items', 'page', 'last_page', 'total'],
  );
  final val = MyGradeDisciplineQueue(
    available: $checkedConvert('available', (v) => v as bool),
    items: $checkedConvert(
      'items',
      (v) => (v as List<dynamic>)
          .map((e) => MyGradePendingRecord.fromJson(e as Map<String, dynamic>))
          .toList(),
    ),
    page: $checkedConvert('page', (v) => (v as num).toInt()),
    lastPage: $checkedConvert('last_page', (v) => (v as num).toInt()),
    total: $checkedConvert('total', (v) => (v as num?)?.toInt()),
  );
  return val;
}, fieldKeyMap: const {'lastPage': 'last_page'});

Map<String, dynamic> _$MyGradeDisciplineQueueToJson(
  MyGradeDisciplineQueue instance,
) => <String, dynamic>{
  'available': instance.available,
  'items': instance.items.map((e) => e.toJson()).toList(),
  'page': instance.page,
  'last_page': instance.lastPage,
  'total': instance.total,
};
