// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'school_assistance_thread_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SchoolAssistanceThreadResponse _$SchoolAssistanceThreadResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SchoolAssistanceThreadResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = SchoolAssistanceThreadResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => SchoolAssistanceThread.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$SchoolAssistanceThreadResponseCodeEnumEnumMap,
        v,
        unknownValue:
            SchoolAssistanceThreadResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$SchoolAssistanceThreadResponseToJson(
  SchoolAssistanceThreadResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _$SchoolAssistanceThreadResponseCodeEnumEnumMap[instance.code]!,
};

const _$SchoolAssistanceThreadResponseCodeEnumEnumMap = {
  SchoolAssistanceThreadResponseCodeEnum.number200: 200,
  SchoolAssistanceThreadResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
