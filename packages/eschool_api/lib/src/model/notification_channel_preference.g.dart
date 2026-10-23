// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'notification_channel_preference.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

NotificationChannelPreference _$NotificationChannelPreferenceFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('NotificationChannelPreference', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['enabled', 'locked', 'reason']);
  final val = NotificationChannelPreference(
    enabled: $checkedConvert('enabled', (v) => v as bool),
    locked: $checkedConvert('locked', (v) => v as bool),
    reason: $checkedConvert('reason', (v) => v as String?),
  );
  return val;
});

Map<String, dynamic> _$NotificationChannelPreferenceToJson(
  NotificationChannelPreference instance,
) => <String, dynamic>{
  'enabled': instance.enabled,
  'locked': instance.locked,
  'reason': instance.reason,
};
