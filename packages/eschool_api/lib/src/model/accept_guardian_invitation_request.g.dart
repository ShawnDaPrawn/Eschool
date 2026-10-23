// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'accept_guardian_invitation_request.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcceptGuardianInvitationRequest _$AcceptGuardianInvitationRequestFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcceptGuardianInvitationRequest', json, ($checkedConvert) {
  final val = AcceptGuardianInvitationRequest(
    password: $checkedConvert('password', (v) => v as String?),
    deviceType: $checkedConvert(
      'device_type',
      (v) => $enumDecodeNullable(
        _$AcceptGuardianInvitationRequestDeviceTypeEnumEnumMap,
        v,
        unknownValue:
            AcceptGuardianInvitationRequestDeviceTypeEnum.unknownDefaultOpenApi,
      ),
    ),
    fcmId: $checkedConvert('fcm_id', (v) => v as String?),
  );
  return val;
}, fieldKeyMap: const {'deviceType': 'device_type', 'fcmId': 'fcm_id'});

Map<String, dynamic> _$AcceptGuardianInvitationRequestToJson(
  AcceptGuardianInvitationRequest instance,
) => <String, dynamic>{
  'password': ?instance.password,
  'device_type':
      ?_$AcceptGuardianInvitationRequestDeviceTypeEnumEnumMap[instance
          .deviceType],
  'fcm_id': ?instance.fcmId,
};

const _$AcceptGuardianInvitationRequestDeviceTypeEnumEnumMap = {
  AcceptGuardianInvitationRequestDeviceTypeEnum.android: 'android',
  AcceptGuardianInvitationRequestDeviceTypeEnum.ios: 'ios',
  AcceptGuardianInvitationRequestDeviceTypeEnum.web: 'web',
  AcceptGuardianInvitationRequestDeviceTypeEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
