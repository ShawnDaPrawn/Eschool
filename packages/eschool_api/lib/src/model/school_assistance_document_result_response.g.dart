// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'school_assistance_document_result_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SchoolAssistanceDocumentResultResponse
_$SchoolAssistanceDocumentResultResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SchoolAssistanceDocumentResultResponse', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = SchoolAssistanceDocumentResultResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => SchoolAssistanceDocumentResult.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$SchoolAssistanceDocumentResultResponseCodeEnumEnumMap,
        v,
        unknownValue: SchoolAssistanceDocumentResultResponseCodeEnum
            .unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$SchoolAssistanceDocumentResultResponseToJson(
  SchoolAssistanceDocumentResultResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code':
      _$SchoolAssistanceDocumentResultResponseCodeEnumEnumMap[instance.code]!,
};

const _$SchoolAssistanceDocumentResultResponseCodeEnumEnumMap = {
  SchoolAssistanceDocumentResultResponseCodeEnum.number200: 200,
  SchoolAssistanceDocumentResultResponseCodeEnum.unknownDefaultOpenApi:
      11184809,
};
