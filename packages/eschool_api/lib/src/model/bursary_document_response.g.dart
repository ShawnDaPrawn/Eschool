// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'bursary_document_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

BursaryDocumentResponse _$BursaryDocumentResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('BursaryDocumentResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = BursaryDocumentResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => BursaryDocument.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$BursaryDocumentResponseCodeEnumEnumMap,
        v,
        unknownValue: BursaryDocumentResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$BursaryDocumentResponseToJson(
  BursaryDocumentResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _$BursaryDocumentResponseCodeEnumEnumMap[instance.code]!,
};

const _$BursaryDocumentResponseCodeEnumEnumMap = {
  BursaryDocumentResponseCodeEnum.number200: 200,
  BursaryDocumentResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
