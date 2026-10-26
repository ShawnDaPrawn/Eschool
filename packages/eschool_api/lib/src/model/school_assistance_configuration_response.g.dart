// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'school_assistance_configuration_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SchoolAssistanceConfigurationResponse
_$SchoolAssistanceConfigurationResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SchoolAssistanceConfigurationResponse', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = SchoolAssistanceConfigurationResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => SchoolAssistanceConfiguration.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$SchoolAssistanceConfigurationResponseCodeEnumEnumMap,
        v,
        unknownValue:
            SchoolAssistanceConfigurationResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$SchoolAssistanceConfigurationResponseToJson(
  SchoolAssistanceConfigurationResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code':
      _$SchoolAssistanceConfigurationResponseCodeEnumEnumMap[instance.code]!,
};

const _$SchoolAssistanceConfigurationResponseCodeEnumEnumMap = {
  SchoolAssistanceConfigurationResponseCodeEnum.number200: 200,
  SchoolAssistanceConfigurationResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
