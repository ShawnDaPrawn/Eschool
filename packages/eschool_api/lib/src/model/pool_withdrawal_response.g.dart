// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'pool_withdrawal_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PoolWithdrawalResponse _$PoolWithdrawalResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('PoolWithdrawalResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = PoolWithdrawalResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => PoolWithdrawal.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => v == null ? null : PoolMemberPageResponseCode.fromJson(v),
    ),
  );
  return val;
});

Map<String, dynamic> _$PoolWithdrawalResponseToJson(
  PoolWithdrawalResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code?.toJson(),
};
