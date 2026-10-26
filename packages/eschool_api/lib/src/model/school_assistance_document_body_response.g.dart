// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'school_assistance_document_body_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SchoolAssistanceDocumentBodyResponse
_$SchoolAssistanceDocumentBodyResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SchoolAssistanceDocumentBodyResponse', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = SchoolAssistanceDocumentBodyResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => SchoolAssistanceDocumentBody.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$SchoolAssistanceDocumentBodyResponseCodeEnumEnumMap,
        v,
        unknownValue:
            SchoolAssistanceDocumentBodyResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$SchoolAssistanceDocumentBodyResponseToJson(
  SchoolAssistanceDocumentBodyResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _$SchoolAssistanceDocumentBodyResponseCodeEnumEnumMap[instance.code]!,
};

const _$SchoolAssistanceDocumentBodyResponseCodeEnumEnumMap = {
  SchoolAssistanceDocumentBodyResponseCodeEnum.number200: 200,
  SchoolAssistanceDocumentBodyResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
