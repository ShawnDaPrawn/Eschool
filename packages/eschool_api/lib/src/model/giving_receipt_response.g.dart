// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'giving_receipt_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GivingReceiptResponse _$GivingReceiptResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('GivingReceiptResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = GivingReceiptResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => GivingReceipt.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$GivingReceiptResponseCodeEnumEnumMap,
        v,
        unknownValue: GivingReceiptResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$GivingReceiptResponseToJson(
  GivingReceiptResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _$GivingReceiptResponseCodeEnumEnumMap[instance.code]!,
};

const _$GivingReceiptResponseCodeEnumEnumMap = {
  GivingReceiptResponseCodeEnum.number200: 200,
  GivingReceiptResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
