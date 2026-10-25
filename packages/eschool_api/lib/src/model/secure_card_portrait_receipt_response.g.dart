// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'secure_card_portrait_receipt_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SecureCardPortraitReceiptResponse _$SecureCardPortraitReceiptResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SecureCardPortraitReceiptResponse', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = SecureCardPortraitReceiptResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => SecureCardPortraitReceipt.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$SecureCardPortraitReceiptResponseCodeEnumEnumMap,
        v,
        unknownValue:
            SecureCardPortraitReceiptResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$SecureCardPortraitReceiptResponseToJson(
  SecureCardPortraitReceiptResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _$SecureCardPortraitReceiptResponseCodeEnumEnumMap[instance.code]!,
};

const _$SecureCardPortraitReceiptResponseCodeEnumEnumMap = {
  SecureCardPortraitReceiptResponseCodeEnum.number200: 200,
  SecureCardPortraitReceiptResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
