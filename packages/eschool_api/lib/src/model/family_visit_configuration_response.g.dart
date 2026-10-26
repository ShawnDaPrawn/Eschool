// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'family_visit_configuration_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FamilyVisitConfigurationResponse _$FamilyVisitConfigurationResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('FamilyVisitConfigurationResponse', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = FamilyVisitConfigurationResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => FamilyVisitConfiguration.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$FamilyVisitConfigurationResponseCodeEnumEnumMap,
        v,
        unknownValue:
            FamilyVisitConfigurationResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$FamilyVisitConfigurationResponseToJson(
  FamilyVisitConfigurationResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _$FamilyVisitConfigurationResponseCodeEnumEnumMap[instance.code]!,
};

const _$FamilyVisitConfigurationResponseCodeEnumEnumMap = {
  FamilyVisitConfigurationResponseCodeEnum.number200: 200,
  FamilyVisitConfigurationResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
