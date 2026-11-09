// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'pool_booking_list_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PoolBookingListResponse _$PoolBookingListResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('PoolBookingListResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = PoolBookingListResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => PoolBookingList.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => v == null ? null : PoolMemberPageResponseCode.fromJson(v),
    ),
  );
  return val;
});

Map<String, dynamic> _$PoolBookingListResponseToJson(
  PoolBookingListResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code?.toJson(),
};
