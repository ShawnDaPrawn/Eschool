// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'pool_own_bookings.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PoolOwnBookings _$PoolOwnBookingsFromJson(Map<String, dynamic> json) =>
    $checkedCreate('PoolOwnBookings', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['vetted', 'items']);
      final val = PoolOwnBookings(
        vetted: $checkedConvert('vetted', (v) => v as bool),
        items: $checkedConvert(
          'items',
          (v) => (v as List<dynamic>)
              .map((e) => PoolOwnBooking.fromJson(e as Map<String, dynamic>))
              .toList(),
        ),
      );
      return val;
    });

Map<String, dynamic> _$PoolOwnBookingsToJson(PoolOwnBookings instance) =>
    <String, dynamic>{
      'vetted': instance.vetted,
      'items': instance.items.map((e) => e.toJson()).toList(),
    };
