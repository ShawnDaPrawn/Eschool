// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'activity_group_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ActivityGroupWrite _$ActivityGroupWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'ActivityGroupWrite',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'operation_id',
        'revision',
        'session_year_id',
        'name',
        'discipline',
        'season',
        'starts_on',
        'ends_on',
        'gender',
        'active',
      ],
    );
    final val = ActivityGroupWrite(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      sessionYearId: $checkedConvert(
        'session_year_id',
        (v) => (v as num).toInt(),
      ),
      parentId: $checkedConvert('parent_id', (v) => (v as num?)?.toInt()),
      name: $checkedConvert('name', (v) => v as String),
      discipline: $checkedConvert('discipline', (v) => v as String),
      season: $checkedConvert('season', (v) => v as String),
      startsOn: $checkedConvert('starts_on', (v) => v as String),
      endsOn: $checkedConvert('ends_on', (v) => v as String),
      gender: $checkedConvert(
        'gender',
        (v) => $enumDecode(
          _$ActivityGroupWriteGenderEnumEnumMap,
          v,
          unknownValue: ActivityGroupWriteGenderEnum.unknownDefaultOpenApi,
        ),
      ),
      minimumGrade: $checkedConvert(
        'minimum_grade',
        (v) => (v as num?)?.toInt(),
      ),
      maximumGrade: $checkedConvert(
        'maximum_grade',
        (v) => (v as num?)?.toInt(),
      ),
      minimumAge: $checkedConvert('minimum_age', (v) => (v as num?)?.toInt()),
      maximumAge: $checkedConvert('maximum_age', (v) => (v as num?)?.toInt()),
      active: $checkedConvert('active', (v) => v as bool),
    );
    return val;
  },
  fieldKeyMap: const {
    'operationId': 'operation_id',
    'sessionYearId': 'session_year_id',
    'parentId': 'parent_id',
    'startsOn': 'starts_on',
    'endsOn': 'ends_on',
    'minimumGrade': 'minimum_grade',
    'maximumGrade': 'maximum_grade',
    'minimumAge': 'minimum_age',
    'maximumAge': 'maximum_age',
  },
);

Map<String, dynamic> _$ActivityGroupWriteToJson(ActivityGroupWrite instance) =>
    <String, dynamic>{
      'operation_id': instance.operationId,
      'revision': instance.revision,
      'session_year_id': instance.sessionYearId,
      'parent_id': ?instance.parentId,
      'name': instance.name,
      'discipline': instance.discipline,
      'season': instance.season,
      'starts_on': instance.startsOn,
      'ends_on': instance.endsOn,
      'gender': _$ActivityGroupWriteGenderEnumEnumMap[instance.gender]!,
      'minimum_grade': ?instance.minimumGrade,
      'maximum_grade': ?instance.maximumGrade,
      'minimum_age': ?instance.minimumAge,
      'maximum_age': ?instance.maximumAge,
      'active': instance.active,
    };

const _$ActivityGroupWriteGenderEnumEnumMap = {
  ActivityGroupWriteGenderEnum.any: 'any',
  ActivityGroupWriteGenderEnum.male: 'male',
  ActivityGroupWriteGenderEnum.female: 'female',
  ActivityGroupWriteGenderEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
