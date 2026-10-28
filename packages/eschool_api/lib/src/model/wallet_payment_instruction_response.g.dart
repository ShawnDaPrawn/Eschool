// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wallet_payment_instruction_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WalletPaymentInstructionResponse _$WalletPaymentInstructionResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('WalletPaymentInstructionResponse', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = WalletPaymentInstructionResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => WalletPaymentInstruction.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$WalletPaymentInstructionResponseToJson(
  WalletPaymentInstructionResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
