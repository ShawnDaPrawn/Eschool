// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'pool_booking_list.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PoolBookingList _$PoolBookingListFromJson(Map<String, dynamic> json) =>
    $checkedCreate('PoolBookingList', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['items']);
      final val = PoolBookingList(
        items: $checkedConvert(
          'items',
          (v) => (v as List<dynamic>)
              .map((e) => PoolBookingRecord.fromJson(e as Map<String, dynamic>))
              .toList(),
        ),
      );
      return val;
    });

Map<String, dynamic> _$PoolBookingListToJson(PoolBookingList instance) =>
    <String, dynamic>{'items': instance.items.map((e) => e.toJson()).toList()};
