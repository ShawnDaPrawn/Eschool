// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'teaching_draft_configuration_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TeachingDraftConfigurationResponse _$TeachingDraftConfigurationResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('TeachingDraftConfigurationResponse', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = TeachingDraftConfigurationResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => TeachingDraftConfiguration.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$TeachingDraftConfigurationResponseCodeEnumEnumMap,
        v,
        unknownValue:
            TeachingDraftConfigurationResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$TeachingDraftConfigurationResponseToJson(
  TeachingDraftConfigurationResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _$TeachingDraftConfigurationResponseCodeEnumEnumMap[instance.code]!,
};

const _$TeachingDraftConfigurationResponseCodeEnumEnumMap = {
  TeachingDraftConfigurationResponseCodeEnum.number200: 200,
  TeachingDraftConfigurationResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
