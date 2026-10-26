// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'family_consent_created_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FamilyConsentCreatedResponse _$FamilyConsentCreatedResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('FamilyConsentCreatedResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = FamilyConsentCreatedResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => FamilyConsentCreated.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$FamilyConsentCreatedResponseCodeEnumEnumMap,
        v,
        unknownValue:
            FamilyConsentCreatedResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$FamilyConsentCreatedResponseToJson(
  FamilyConsentCreatedResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _$FamilyConsentCreatedResponseCodeEnumEnumMap[instance.code]!,
};

const _$FamilyConsentCreatedResponseCodeEnumEnumMap = {
  FamilyConsentCreatedResponseCodeEnum.number200: 200,
  FamilyConsentCreatedResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
