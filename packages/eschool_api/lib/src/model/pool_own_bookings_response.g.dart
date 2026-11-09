// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'pool_own_bookings_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PoolOwnBookingsResponse _$PoolOwnBookingsResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('PoolOwnBookingsResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = PoolOwnBookingsResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => PoolOwnBookings.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => v == null ? null : PoolMemberPageResponseCode.fromJson(v),
    ),
  );
  return val;
});

Map<String, dynamic> _$PoolOwnBookingsResponseToJson(
  PoolOwnBookingsResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code?.toJson(),
};
