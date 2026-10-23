// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'update_my_notification_preferences_request.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

UpdateMyNotificationPreferencesRequest
_$UpdateMyNotificationPreferencesRequestFromJson(Map<String, dynamic> json) =>
    $checkedCreate('UpdateMyNotificationPreferencesRequest', json, (
      $checkedConvert,
    ) {
      $checkKeys(json, requiredKeys: const ['type', 'channel', 'enabled']);
      final val = UpdateMyNotificationPreferencesRequest(
        type: $checkedConvert('type', (v) => v as String),
        channel: $checkedConvert(
          'channel',
          (v) => $enumDecode(
            _$UpdateMyNotificationPreferencesRequestChannelEnumEnumMap,
            v,
            unknownValue: UpdateMyNotificationPreferencesRequestChannelEnum
                .unknownDefaultOpenApi,
          ),
        ),
        enabled: $checkedConvert('enabled', (v) => v as bool),
      );
      return val;
    });

Map<String, dynamic> _$UpdateMyNotificationPreferencesRequestToJson(
  UpdateMyNotificationPreferencesRequest instance,
) => <String, dynamic>{
  'type': instance.type,
  'channel':
      _$UpdateMyNotificationPreferencesRequestChannelEnumEnumMap[instance
          .channel]!,
  'enabled': instance.enabled,
};

const _$UpdateMyNotificationPreferencesRequestChannelEnumEnumMap = {
  UpdateMyNotificationPreferencesRequestChannelEnum.push: 'push',
  UpdateMyNotificationPreferencesRequestChannelEnum.email: 'email',
  UpdateMyNotificationPreferencesRequestChannelEnum.inApp: 'in_app',
  UpdateMyNotificationPreferencesRequestChannelEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
