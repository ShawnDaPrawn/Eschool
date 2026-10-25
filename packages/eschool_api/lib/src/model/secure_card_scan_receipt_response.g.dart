// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'secure_card_scan_receipt_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SecureCardScanReceiptResponse _$SecureCardScanReceiptResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SecureCardScanReceiptResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = SecureCardScanReceiptResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => SecureCardScanReceipt.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$SecureCardScanReceiptResponseCodeEnumEnumMap,
        v,
        unknownValue:
            SecureCardScanReceiptResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$SecureCardScanReceiptResponseToJson(
  SecureCardScanReceiptResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _$SecureCardScanReceiptResponseCodeEnumEnumMap[instance.code]!,
};

const _$SecureCardScanReceiptResponseCodeEnumEnumMap = {
  SecureCardScanReceiptResponseCodeEnum.number200: 200,
  SecureCardScanReceiptResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
