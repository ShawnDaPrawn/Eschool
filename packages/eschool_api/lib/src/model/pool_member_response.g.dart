// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'pool_member_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PoolMemberResponse _$PoolMemberResponseFromJson(Map<String, dynamic> json) =>
    $checkedCreate('PoolMemberResponse', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['error', 'message', 'data', 'code'],
      );
      final val = PoolMemberResponse(
        error: $checkedConvert('error', (v) => v as bool),
        message: $checkedConvert('message', (v) => v as String),
        data: $checkedConvert(
          'data',
          (v) => PoolMemberDetail.fromJson(v as Map<String, dynamic>),
        ),
        code: $checkedConvert(
          'code',
          (v) => v == null ? null : PoolMemberPageResponseCode.fromJson(v),
        ),
      );
      return val;
    });

Map<String, dynamic> _$PoolMemberResponseToJson(PoolMemberResponse instance) =>
    <String, dynamic>{
      'error': instance.error,
      'message': instance.message,
      'data': instance.data.toJson(),
      'code': instance.code?.toJson(),
    };
