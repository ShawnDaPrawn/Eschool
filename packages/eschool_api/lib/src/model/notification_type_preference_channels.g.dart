// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'notification_type_preference_channels.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

NotificationTypePreferenceChannels _$NotificationTypePreferenceChannelsFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('NotificationTypePreferenceChannels', json, (
  $checkedConvert,
) {
  final val = NotificationTypePreferenceChannels(
    push: $checkedConvert(
      'push',
      (v) => v == null
          ? null
          : NotificationChannelPreference.fromJson(v as Map<String, dynamic>),
    ),
    email: $checkedConvert(
      'email',
      (v) => v == null
          ? null
          : NotificationChannelPreference.fromJson(v as Map<String, dynamic>),
    ),
    inApp: $checkedConvert(
      'in_app',
      (v) => v == null
          ? null
          : NotificationChannelPreference.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
}, fieldKeyMap: const {'inApp': 'in_app'});

Map<String, dynamic> _$NotificationTypePreferenceChannelsToJson(
  NotificationTypePreferenceChannels instance,
) => <String, dynamic>{
  'push': ?instance.push?.toJson(),
  'email': ?instance.email?.toJson(),
  'in_app': ?instance.inApp?.toJson(),
};
