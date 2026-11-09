// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'family_summary_preferences_response_data.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FamilySummaryPreferencesResponseData
_$FamilySummaryPreferencesResponseDataFromJson(Map<String, dynamic> json) =>
    $checkedCreate('FamilySummaryPreferencesResponseData', json, (
      $checkedConvert,
    ) {
      $checkKeys(
        json,
        requiredKeys: const [
          'enabled',
          'send_at',
          'timezone',
          'revision',
          'quiet_hours',
        ],
      );
      final val = FamilySummaryPreferencesResponseData(
        enabled: $checkedConvert('enabled', (v) => v as bool),
        sendAt: $checkedConvert('send_at', (v) => v as String),
        timezone: $checkedConvert('timezone', (v) => v as String),
        revision: $checkedConvert('revision', (v) => (v as num).toInt()),
        quietHours: $checkedConvert(
          'quiet_hours',
          (v) => FamilySummaryPreferencesResponseDataQuietHours.fromJson(
            v as Map<String, dynamic>,
          ),
        ),
      );
      return val;
    }, fieldKeyMap: const {'sendAt': 'send_at', 'quietHours': 'quiet_hours'});

Map<String, dynamic> _$FamilySummaryPreferencesResponseDataToJson(
  FamilySummaryPreferencesResponseData instance,
) => <String, dynamic>{
  'enabled': instance.enabled,
  'send_at': instance.sendAt,
  'timezone': instance.timezone,
  'revision': instance.revision,
  'quiet_hours': instance.quietHours.toJson(),
};
