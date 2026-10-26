// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'family_form_configuration_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FamilyFormConfigurationResponse _$FamilyFormConfigurationResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('FamilyFormConfigurationResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = FamilyFormConfigurationResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => FamilyFormConfiguration.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$FamilyFormConfigurationResponseCodeEnumEnumMap,
        v,
        unknownValue:
            FamilyFormConfigurationResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$FamilyFormConfigurationResponseToJson(
  FamilyFormConfigurationResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _$FamilyFormConfigurationResponseCodeEnumEnumMap[instance.code]!,
};

const _$FamilyFormConfigurationResponseCodeEnumEnumMap = {
  FamilyFormConfigurationResponseCodeEnum.number200: 200,
  FamilyFormConfigurationResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
