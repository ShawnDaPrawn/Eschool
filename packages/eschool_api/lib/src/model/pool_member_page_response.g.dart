// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'pool_member_page_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PoolMemberPageResponse _$PoolMemberPageResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('PoolMemberPageResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = PoolMemberPageResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => PoolMemberPage.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => v == null ? null : PoolMemberPageResponseCode.fromJson(v),
    ),
  );
  return val;
});

Map<String, dynamic> _$PoolMemberPageResponseToJson(
  PoolMemberPageResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code?.toJson(),
};
