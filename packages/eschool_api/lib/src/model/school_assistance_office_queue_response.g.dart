// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'school_assistance_office_queue_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SchoolAssistanceOfficeQueueResponse
_$SchoolAssistanceOfficeQueueResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SchoolAssistanceOfficeQueueResponse', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = SchoolAssistanceOfficeQueueResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => SchoolAssistanceOfficeQueue.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$SchoolAssistanceOfficeQueueResponseCodeEnumEnumMap,
        v,
        unknownValue:
            SchoolAssistanceOfficeQueueResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$SchoolAssistanceOfficeQueueResponseToJson(
  SchoolAssistanceOfficeQueueResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _$SchoolAssistanceOfficeQueueResponseCodeEnumEnumMap[instance.code]!,
};

const _$SchoolAssistanceOfficeQueueResponseCodeEnumEnumMap = {
  SchoolAssistanceOfficeQueueResponseCodeEnum.number200: 200,
  SchoolAssistanceOfficeQueueResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
