// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'discipline_detention.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DisciplineDetention _$DisciplineDetentionFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'DisciplineDetention',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'id',
            'session_year_id',
            'grade_level_id',
            'supervisor_id',
            'on_date',
            'starts_at',
            'ends_at',
            'timezone',
            'capacity',
            'status',
            'revision',
          ],
        );
        final val = DisciplineDetention(
          id: $checkedConvert('id', (v) => (v as num).toInt()),
          sessionYearId: $checkedConvert(
            'session_year_id',
            (v) => (v as num).toInt(),
          ),
          gradeLevelId: $checkedConvert(
            'grade_level_id',
            (v) => (v as num).toInt(),
          ),
          supervisorId: $checkedConvert(
            'supervisor_id',
            (v) => (v as num).toInt(),
          ),
          onDate: $checkedConvert('on_date', (v) => v as String),
          startsAt: $checkedConvert('starts_at', (v) => v as String),
          endsAt: $checkedConvert('ends_at', (v) => v as String),
          timezone: $checkedConvert('timezone', (v) => v as String),
          capacity: $checkedConvert('capacity', (v) => (v as num).toInt()),
          status: $checkedConvert(
            'status',
            (v) => $enumDecode(
              _$DisciplineDetentionStatusEnumEnumMap,
              v,
              unknownValue: DisciplineDetentionStatusEnum.unknownDefaultOpenApi,
            ),
          ),
          revision: $checkedConvert('revision', (v) => (v as num).toInt()),
        );
        return val;
      },
      fieldKeyMap: const {
        'sessionYearId': 'session_year_id',
        'gradeLevelId': 'grade_level_id',
        'supervisorId': 'supervisor_id',
        'onDate': 'on_date',
        'startsAt': 'starts_at',
        'endsAt': 'ends_at',
      },
    );

Map<String, dynamic> _$DisciplineDetentionToJson(
  DisciplineDetention instance,
) => <String, dynamic>{
  'id': instance.id,
  'session_year_id': instance.sessionYearId,
  'grade_level_id': instance.gradeLevelId,
  'supervisor_id': instance.supervisorId,
  'on_date': instance.onDate,
  'starts_at': instance.startsAt,
  'ends_at': instance.endsAt,
  'timezone': instance.timezone,
  'capacity': instance.capacity,
  'status': _$DisciplineDetentionStatusEnumEnumMap[instance.status]!,
  'revision': instance.revision,
};

const _$DisciplineDetentionStatusEnumEnumMap = {
  DisciplineDetentionStatusEnum.planned: 'planned',
  DisciplineDetentionStatusEnum.cancelled: 'cancelled',
  DisciplineDetentionStatusEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
