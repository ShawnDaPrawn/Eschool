// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'school_assistance_thread_page_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SchoolAssistanceThreadPageResponse _$SchoolAssistanceThreadPageResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SchoolAssistanceThreadPageResponse', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = SchoolAssistanceThreadPageResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => SchoolAssistanceThreadPage.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$SchoolAssistanceThreadPageResponseCodeEnumEnumMap,
        v,
        unknownValue:
            SchoolAssistanceThreadPageResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$SchoolAssistanceThreadPageResponseToJson(
  SchoolAssistanceThreadPageResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _$SchoolAssistanceThreadPageResponseCodeEnumEnumMap[instance.code]!,
};

const _$SchoolAssistanceThreadPageResponseCodeEnumEnumMap = {
  SchoolAssistanceThreadPageResponseCodeEnum.number200: 200,
  SchoolAssistanceThreadPageResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
