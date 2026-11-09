// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'pool_sign_out_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PoolSignOutResponse _$PoolSignOutResponseFromJson(Map<String, dynamic> json) =>
    $checkedCreate('PoolSignOutResponse', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['error', 'message', 'data', 'code'],
      );
      final val = PoolSignOutResponse(
        error: $checkedConvert('error', (v) => v as bool),
        message: $checkedConvert('message', (v) => v as String),
        data: $checkedConvert('data', (v) => _onlyNullFromJson(v)),
        code: $checkedConvert(
          'code',
          (v) => v == null ? null : PoolMemberPageResponseCode.fromJson(v),
        ),
      );
      return val;
    });

Map<String, dynamic> _$PoolSignOutResponseToJson(
  PoolSignOutResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': _onlyNullToJson(instance.data),
  'code': instance.code?.toJson(),
};
