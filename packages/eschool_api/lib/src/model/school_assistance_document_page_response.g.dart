// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'school_assistance_document_page_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SchoolAssistanceDocumentPageResponse
_$SchoolAssistanceDocumentPageResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SchoolAssistanceDocumentPageResponse', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = SchoolAssistanceDocumentPageResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => SchoolAssistanceDocumentPage.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$SchoolAssistanceDocumentPageResponseCodeEnumEnumMap,
        v,
        unknownValue:
            SchoolAssistanceDocumentPageResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$SchoolAssistanceDocumentPageResponseToJson(
  SchoolAssistanceDocumentPageResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _$SchoolAssistanceDocumentPageResponseCodeEnumEnumMap[instance.code]!,
};

const _$SchoolAssistanceDocumentPageResponseCodeEnumEnumMap = {
  SchoolAssistanceDocumentPageResponseCodeEnum.number200: 200,
  SchoolAssistanceDocumentPageResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
