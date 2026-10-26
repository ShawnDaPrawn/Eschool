// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'school_assistance_thread_result_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SchoolAssistanceThreadResultResponse
_$SchoolAssistanceThreadResultResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SchoolAssistanceThreadResultResponse', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = SchoolAssistanceThreadResultResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => SchoolAssistanceThreadResult.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$SchoolAssistanceThreadResultResponseCodeEnumEnumMap,
        v,
        unknownValue:
            SchoolAssistanceThreadResultResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$SchoolAssistanceThreadResultResponseToJson(
  SchoolAssistanceThreadResultResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _$SchoolAssistanceThreadResultResponseCodeEnumEnumMap[instance.code]!,
};

const _$SchoolAssistanceThreadResultResponseCodeEnumEnumMap = {
  SchoolAssistanceThreadResultResponseCodeEnum.number200: 200,
  SchoolAssistanceThreadResultResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
