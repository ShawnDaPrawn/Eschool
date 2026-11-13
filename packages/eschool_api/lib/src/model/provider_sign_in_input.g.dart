// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'provider_sign_in_input.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ProviderSignInInput _$ProviderSignInInputFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'ProviderSignInInput',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const ['school', 'app', 'id_token', 'nonce'],
        );
        final val = ProviderSignInInput(
          school: $checkedConvert('school', (v) => v as String),
          app: $checkedConvert(
            'app',
            (v) => $enumDecode(
              _$ProviderSignInInputAppEnumEnumMap,
              v,
              unknownValue: ProviderSignInInputAppEnum.unknownDefaultOpenApi,
            ),
          ),
          idToken: $checkedConvert('id_token', (v) => v as String),
          nonce: $checkedConvert('nonce', (v) => v as String),
          fcmId: $checkedConvert('fcm_id', (v) => v as String?),
          deviceType: $checkedConvert('device_type', (v) => v as String?),
        );
        return val;
      },
      fieldKeyMap: const {
        'idToken': 'id_token',
        'fcmId': 'fcm_id',
        'deviceType': 'device_type',
      },
    );

Map<String, dynamic> _$ProviderSignInInputToJson(
  ProviderSignInInput instance,
) => <String, dynamic>{
  'school': instance.school,
  'app': _$ProviderSignInInputAppEnumEnumMap[instance.app]!,
  'id_token': instance.idToken,
  'nonce': instance.nonce,
  'fcm_id': ?instance.fcmId,
  'device_type': ?instance.deviceType,
};

const _$ProviderSignInInputAppEnumEnumMap = {
  ProviderSignInInputAppEnum.teacher: 'teacher',
  ProviderSignInInputAppEnum.student: 'student',
  ProviderSignInInputAppEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
