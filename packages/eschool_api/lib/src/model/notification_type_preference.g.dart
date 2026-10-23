// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'notification_type_preference.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

NotificationTypePreference _$NotificationTypePreferenceFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('NotificationTypePreference', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['type', 'label', 'module', 'channels']);
  final val = NotificationTypePreference(
    type: $checkedConvert('type', (v) => v as String),
    label: $checkedConvert('label', (v) => v as String),
    module: $checkedConvert('module', (v) => v as String?),
    channels: $checkedConvert(
      'channels',
      (v) => NotificationTypePreferenceChannels.fromJson(
        v as Map<String, dynamic>,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$NotificationTypePreferenceToJson(
  NotificationTypePreference instance,
) => <String, dynamic>{
  'type': instance.type,
  'label': instance.label,
  'module': instance.module,
  'channels': instance.channels.toJson(),
};
