// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'secure_offline_receipt_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SecureOfflineReceiptResponse _$SecureOfflineReceiptResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SecureOfflineReceiptResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = SecureOfflineReceiptResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => SecureOfflineReceipt.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$SecureOfflineReceiptResponseCodeEnumEnumMap,
        v,
        unknownValue:
            SecureOfflineReceiptResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$SecureOfflineReceiptResponseToJson(
  SecureOfflineReceiptResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _$SecureOfflineReceiptResponseCodeEnumEnumMap[instance.code]!,
};

const _$SecureOfflineReceiptResponseCodeEnumEnumMap = {
  SecureOfflineReceiptResponseCodeEnum.number200: 200,
  SecureOfflineReceiptResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
