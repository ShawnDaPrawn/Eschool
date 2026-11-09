// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'family_summary_preferences_response_data_quiet_hours.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FamilySummaryPreferencesResponseDataQuietHours
_$FamilySummaryPreferencesResponseDataQuietHoursFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('FamilySummaryPreferencesResponseDataQuietHours', json, (
  $checkedConvert,
) {
  $checkKeys(
    json,
    requiredKeys: const ['enabled', 'starts_at', 'ends_at', 'timezone'],
  );
  final val = FamilySummaryPreferencesResponseDataQuietHours(
    enabled: $checkedConvert('enabled', (v) => v as bool),
    startsAt: $checkedConvert('starts_at', (v) => v as String),
    endsAt: $checkedConvert('ends_at', (v) => v as String),
    timezone: $checkedConvert('timezone', (v) => v as String),
  );
  return val;
}, fieldKeyMap: const {'startsAt': 'starts_at', 'endsAt': 'ends_at'});

Map<String, dynamic> _$FamilySummaryPreferencesResponseDataQuietHoursToJson(
  FamilySummaryPreferencesResponseDataQuietHours instance,
) => <String, dynamic>{
  'enabled': instance.enabled,
  'starts_at': instance.startsAt,
  'ends_at': instance.endsAt,
  'timezone': instance.timezone,
};
