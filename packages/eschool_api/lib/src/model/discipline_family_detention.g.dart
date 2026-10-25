// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'discipline_family_detention.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DisciplineFamilyDetention _$DisciplineFamilyDetentionFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'DisciplineFamilyDetention',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'session_id',
        'on_date',
        'starts_at',
        'ends_at',
        'timezone',
        'location',
        'attendance',
        'active',
      ],
    );
    final val = DisciplineFamilyDetention(
      sessionId: $checkedConvert('session_id', (v) => (v as num).toInt()),
      onDate: $checkedConvert('on_date', (v) => v as String),
      startsAt: $checkedConvert('starts_at', (v) => v as String),
      endsAt: $checkedConvert('ends_at', (v) => v as String),
      timezone: $checkedConvert('timezone', (v) => v as String),
      location: $checkedConvert('location', (v) => v as String),
      attendance: $checkedConvert(
        'attendance',
        (v) => $enumDecodeNullable(
          _$DisciplineFamilyDetentionAttendanceEnumEnumMap,
          v,
          unknownValue:
              DisciplineFamilyDetentionAttendanceEnum.unknownDefaultOpenApi,
        ),
      ),
      active: $checkedConvert('active', (v) => v as bool),
    );
    return val;
  },
  fieldKeyMap: const {
    'sessionId': 'session_id',
    'onDate': 'on_date',
    'startsAt': 'starts_at',
    'endsAt': 'ends_at',
  },
);

Map<String, dynamic> _$DisciplineFamilyDetentionToJson(
  DisciplineFamilyDetention instance,
) => <String, dynamic>{
  'session_id': instance.sessionId,
  'on_date': instance.onDate,
  'starts_at': instance.startsAt,
  'ends_at': instance.endsAt,
  'timezone': instance.timezone,
  'location': instance.location,
  'attendance':
      _$DisciplineFamilyDetentionAttendanceEnumEnumMap[instance.attendance],
  'active': instance.active,
};

const _$DisciplineFamilyDetentionAttendanceEnumEnumMap = {
  DisciplineFamilyDetentionAttendanceEnum.present: 'present',
  DisciplineFamilyDetentionAttendanceEnum.absent: 'absent',
  DisciplineFamilyDetentionAttendanceEnum.excused: 'excused',
  DisciplineFamilyDetentionAttendanceEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
