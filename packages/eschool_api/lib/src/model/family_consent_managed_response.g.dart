// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'family_consent_managed_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FamilyConsentManagedResponse _$FamilyConsentManagedResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('FamilyConsentManagedResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = FamilyConsentManagedResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => FamilyConsentManaged.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$FamilyConsentManagedResponseCodeEnumEnumMap,
        v,
        unknownValue:
            FamilyConsentManagedResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$FamilyConsentManagedResponseToJson(
  FamilyConsentManagedResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _$FamilyConsentManagedResponseCodeEnumEnumMap[instance.code]!,
};

const _$FamilyConsentManagedResponseCodeEnumEnumMap = {
  FamilyConsentManagedResponseCodeEnum.number200: 200,
  FamilyConsentManagedResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
