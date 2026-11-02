// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'quiet_hours_response_data.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

QuietHoursResponseData _$QuietHoursResponseDataFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'QuietHoursResponseData',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'enabled',
        'starts_at',
        'ends_at',
        'timezone',
        'exempt_types',
      ],
    );
    final val = QuietHoursResponseData(
      enabled: $checkedConvert('enabled', (v) => v as bool),
      startsAt: $checkedConvert('starts_at', (v) => v as String),
      endsAt: $checkedConvert('ends_at', (v) => v as String),
      timezone: $checkedConvert('timezone', (v) => v as String),
      exemptTypes: $checkedConvert(
        'exempt_types',
        (v) => (v as List<dynamic>).map((e) => e as String).toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'startsAt': 'starts_at',
    'endsAt': 'ends_at',
    'exemptTypes': 'exempt_types',
  },
);

Map<String, dynamic> _$QuietHoursResponseDataToJson(
  QuietHoursResponseData instance,
) => <String, dynamic>{
  'enabled': instance.enabled,
  'starts_at': instance.startsAt,
  'ends_at': instance.endsAt,
  'timezone': instance.timezone,
  'exempt_types': instance.exemptTypes,
};
