// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'discipline_detention_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DisciplineDetentionWrite _$DisciplineDetentionWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'DisciplineDetentionWrite',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'operation_id',
        'session_year_id',
        'grade_level_id',
        'supervisor_id',
        'on_date',
        'starts_at',
        'ends_at',
        'location',
        'capacity',
      ],
    );
    final val = DisciplineDetentionWrite(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      sessionYearId: $checkedConvert(
        'session_year_id',
        (v) => (v as num).toInt(),
      ),
      gradeLevelId: $checkedConvert(
        'grade_level_id',
        (v) => (v as num).toInt(),
      ),
      supervisorId: $checkedConvert('supervisor_id', (v) => (v as num).toInt()),
      onDate: $checkedConvert('on_date', (v) => v as String),
      startsAt: $checkedConvert('starts_at', (v) => v as String),
      endsAt: $checkedConvert('ends_at', (v) => v as String),
      location: $checkedConvert('location', (v) => v as String),
      capacity: $checkedConvert('capacity', (v) => (v as num).toInt()),
    );
    return val;
  },
  fieldKeyMap: const {
    'operationId': 'operation_id',
    'sessionYearId': 'session_year_id',
    'gradeLevelId': 'grade_level_id',
    'supervisorId': 'supervisor_id',
    'onDate': 'on_date',
    'startsAt': 'starts_at',
    'endsAt': 'ends_at',
  },
);

Map<String, dynamic> _$DisciplineDetentionWriteToJson(
  DisciplineDetentionWrite instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'session_year_id': instance.sessionYearId,
  'grade_level_id': instance.gradeLevelId,
  'supervisor_id': instance.supervisorId,
  'on_date': instance.onDate,
  'starts_at': instance.startsAt,
  'ends_at': instance.endsAt,
  'location': instance.location,
  'capacity': instance.capacity,
};
