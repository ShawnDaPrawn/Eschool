// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'pool_sign_in_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PoolSignInResponse _$PoolSignInResponseFromJson(Map<String, dynamic> json) =>
    $checkedCreate('PoolSignInResponse', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['error', 'message', 'data', 'code'],
      );
      final val = PoolSignInResponse(
        error: $checkedConvert('error', (v) => v as bool),
        message: $checkedConvert('message', (v) => v as String),
        data: $checkedConvert(
          'data',
          (v) => PoolSignInData.fromJson(v as Map<String, dynamic>),
        ),
        code: $checkedConvert(
          'code',
          (v) => v == null ? null : PoolMemberPageResponseCode.fromJson(v),
        ),
      );
      return val;
    });

Map<String, dynamic> _$PoolSignInResponseToJson(PoolSignInResponse instance) =>
    <String, dynamic>{
      'error': instance.error,
      'message': instance.message,
      'data': instance.data.toJson(),
      'code': instance.code?.toJson(),
    };
