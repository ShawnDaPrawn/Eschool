// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'bursary_queue.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

BursaryQueue _$BursaryQueueFromJson(Map<String, dynamic> json) =>
    $checkedCreate('BursaryQueue', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['total', 'page', 'items']);
      final val = BursaryQueue(
        total: $checkedConvert('total', (v) => (v as num).toInt()),
        page: $checkedConvert('page', (v) => (v as num).toInt()),
        items: $checkedConvert(
          'items',
          (v) => (v as List<dynamic>)
              .map(
                (e) => BursaryApplicationListItemsInner.fromJson(
                  e as Map<String, dynamic>,
                ),
              )
              .toList(),
        ),
      );
      return val;
    });

Map<String, dynamic> _$BursaryQueueToJson(BursaryQueue instance) =>
    <String, dynamic>{
      'total': instance.total,
      'page': instance.page,
      'items': instance.items.map((e) => e.toJson()).toList(),
    };
