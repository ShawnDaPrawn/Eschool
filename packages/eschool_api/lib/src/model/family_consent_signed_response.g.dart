// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'family_consent_signed_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FamilyConsentSignedResponse _$FamilyConsentSignedResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('FamilyConsentSignedResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = FamilyConsentSignedResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => FamilyConsentSigned.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$FamilyConsentSignedResponseCodeEnumEnumMap,
        v,
        unknownValue: FamilyConsentSignedResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$FamilyConsentSignedResponseToJson(
  FamilyConsentSignedResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _$FamilyConsentSignedResponseCodeEnumEnumMap[instance.code]!,
};

const _$FamilyConsentSignedResponseCodeEnumEnumMap = {
  FamilyConsentSignedResponseCodeEnum.number200: 200,
  FamilyConsentSignedResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
