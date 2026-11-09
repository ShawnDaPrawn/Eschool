// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'pool_booking_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PoolBookingResponse _$PoolBookingResponseFromJson(Map<String, dynamic> json) =>
    $checkedCreate('PoolBookingResponse', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['error', 'message', 'data', 'code'],
      );
      final val = PoolBookingResponse(
        error: $checkedConvert('error', (v) => v as bool),
        message: $checkedConvert('message', (v) => v as String),
        data: $checkedConvert(
          'data',
          (v) => PoolBookingRecord.fromJson(v as Map<String, dynamic>),
        ),
        code: $checkedConvert(
          'code',
          (v) => v == null ? null : PoolMemberPageResponseCode.fromJson(v),
        ),
      );
      return val;
    });

Map<String, dynamic> _$PoolBookingResponseToJson(
  PoolBookingResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code?.toJson(),
};
