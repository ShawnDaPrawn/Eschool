// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'family_consent_detail_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FamilyConsentDetailResponse _$FamilyConsentDetailResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('FamilyConsentDetailResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = FamilyConsentDetailResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => FamilyConsentDetail.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$FamilyConsentDetailResponseCodeEnumEnumMap,
        v,
        unknownValue: FamilyConsentDetailResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$FamilyConsentDetailResponseToJson(
  FamilyConsentDetailResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _$FamilyConsentDetailResponseCodeEnumEnumMap[instance.code]!,
};

const _$FamilyConsentDetailResponseCodeEnumEnumMap = {
  FamilyConsentDetailResponseCodeEnum.number200: 200,
  FamilyConsentDetailResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
