// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'pool_profile_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PoolProfileResponse _$PoolProfileResponseFromJson(Map<String, dynamic> json) =>
    $checkedCreate('PoolProfileResponse', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['error', 'message', 'data', 'code'],
      );
      final val = PoolProfileResponse(
        error: $checkedConvert('error', (v) => v as bool),
        message: $checkedConvert('message', (v) => v as String),
        data: $checkedConvert(
          'data',
          (v) => PoolProfile.fromJson(v as Map<String, dynamic>),
        ),
        code: $checkedConvert(
          'code',
          (v) => v == null ? null : PoolMemberPageResponseCode.fromJson(v),
        ),
      );
      return val;
    });

Map<String, dynamic> _$PoolProfileResponseToJson(
  PoolProfileResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code?.toJson(),
};
