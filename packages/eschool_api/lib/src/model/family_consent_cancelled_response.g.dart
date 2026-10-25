// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'family_consent_cancelled_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FamilyConsentCancelledResponse _$FamilyConsentCancelledResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('FamilyConsentCancelledResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = FamilyConsentCancelledResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => FamilyConsentCancelled.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$FamilyConsentCancelledResponseCodeEnumEnumMap,
        v,
        unknownValue:
            FamilyConsentCancelledResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$FamilyConsentCancelledResponseToJson(
  FamilyConsentCancelledResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _$FamilyConsentCancelledResponseCodeEnumEnumMap[instance.code]!,
};

const _$FamilyConsentCancelledResponseCodeEnumEnumMap = {
  FamilyConsentCancelledResponseCodeEnum.number200: 200,
  FamilyConsentCancelledResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
