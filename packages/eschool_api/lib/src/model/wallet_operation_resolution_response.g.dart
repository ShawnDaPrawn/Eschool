// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wallet_operation_resolution_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WalletOperationResolutionResponse _$WalletOperationResolutionResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('WalletOperationResolutionResponse', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = WalletOperationResolutionResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => WalletOperationResolution.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$WalletOperationResolutionResponseToJson(
  WalletOperationResolutionResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
