// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'family_consent_page_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FamilyConsentPageResponse _$FamilyConsentPageResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('FamilyConsentPageResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = FamilyConsentPageResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => FamilyConsentPage.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$FamilyConsentPageResponseCodeEnumEnumMap,
        v,
        unknownValue: FamilyConsentPageResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$FamilyConsentPageResponseToJson(
  FamilyConsentPageResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _$FamilyConsentPageResponseCodeEnumEnumMap[instance.code]!,
};

const _$FamilyConsentPageResponseCodeEnumEnumMap = {
  FamilyConsentPageResponseCodeEnum.number200: 200,
  FamilyConsentPageResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
