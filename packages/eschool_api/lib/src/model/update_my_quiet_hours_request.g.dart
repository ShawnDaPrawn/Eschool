// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'update_my_quiet_hours_request.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

UpdateMyQuietHoursRequest _$UpdateMyQuietHoursRequestFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('UpdateMyQuietHoursRequest', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['enabled', 'starts_at', 'ends_at']);
  final val = UpdateMyQuietHoursRequest(
    enabled: $checkedConvert('enabled', (v) => v as bool),
    startsAt: $checkedConvert('starts_at', (v) => v as String),
    endsAt: $checkedConvert('ends_at', (v) => v as String),
    timezone: $checkedConvert('timezone', (v) => v as String?),
  );
  return val;
}, fieldKeyMap: const {'startsAt': 'starts_at', 'endsAt': 'ends_at'});

Map<String, dynamic> _$UpdateMyQuietHoursRequestToJson(
  UpdateMyQuietHoursRequest instance,
) => <String, dynamic>{
  'enabled': instance.enabled,
  'starts_at': instance.startsAt,
  'ends_at': instance.endsAt,
  'timezone': ?instance.timezone,
};
