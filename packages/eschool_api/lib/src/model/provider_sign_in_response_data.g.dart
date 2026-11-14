// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'provider_sign_in_response_data.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ProviderSignInResponseData _$ProviderSignInResponseDataFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ProviderSignInResponseData', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['token', 'app', 'school']);
  final val = ProviderSignInResponseData(
    token: $checkedConvert('token', (v) => v as String?),
    app: $checkedConvert(
      'app',
      (v) => $enumDecode(
        _$ProviderSignInResponseDataAppEnumEnumMap,
        v,
        unknownValue: ProviderSignInResponseDataAppEnum.unknownDefaultOpenApi,
      ),
    ),
    school: $checkedConvert(
      'school',
      (v) => PoolProfileSchool.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$ProviderSignInResponseDataToJson(
  ProviderSignInResponseData instance,
) => <String, dynamic>{
  'token': instance.token,
  'app': _$ProviderSignInResponseDataAppEnumEnumMap[instance.app]!,
  'school': instance.school.toJson(),
};

const _$ProviderSignInResponseDataAppEnumEnumMap = {
  ProviderSignInResponseDataAppEnum.teacher: 'teacher',
  ProviderSignInResponseDataAppEnum.student: 'student',
  ProviderSignInResponseDataAppEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
