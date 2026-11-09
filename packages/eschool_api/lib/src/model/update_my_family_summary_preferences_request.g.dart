// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'update_my_family_summary_preferences_request.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

UpdateMyFamilySummaryPreferencesRequest
_$UpdateMyFamilySummaryPreferencesRequestFromJson(Map<String, dynamic> json) =>
    $checkedCreate('UpdateMyFamilySummaryPreferencesRequest', json, (
      $checkedConvert,
    ) {
      $checkKeys(json, requiredKeys: const ['enabled', 'send_at']);
      final val = UpdateMyFamilySummaryPreferencesRequest(
        enabled: $checkedConvert('enabled', (v) => v as bool),
        sendAt: $checkedConvert('send_at', (v) => v as String),
        timezone: $checkedConvert('timezone', (v) => v as String?),
      );
      return val;
    }, fieldKeyMap: const {'sendAt': 'send_at'});

Map<String, dynamic> _$UpdateMyFamilySummaryPreferencesRequestToJson(
  UpdateMyFamilySummaryPreferencesRequest instance,
) => <String, dynamic>{
  'enabled': instance.enabled,
  'send_at': instance.sendAt,
  'timezone': ?instance.timezone,
};
