// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'teaching_photo_receipt_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TeachingPhotoReceiptResponse _$TeachingPhotoReceiptResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('TeachingPhotoReceiptResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = TeachingPhotoReceiptResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => TeachingPhotoReceipt.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$TeachingPhotoReceiptResponseCodeEnumEnumMap,
        v,
        unknownValue:
            TeachingPhotoReceiptResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$TeachingPhotoReceiptResponseToJson(
  TeachingPhotoReceiptResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _$TeachingPhotoReceiptResponseCodeEnumEnumMap[instance.code]!,
};

const _$TeachingPhotoReceiptResponseCodeEnumEnumMap = {
  TeachingPhotoReceiptResponseCodeEnum.number200: 200,
  TeachingPhotoReceiptResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
