// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'activity_group.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ActivityGroup _$ActivityGroupFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'ActivityGroup',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'school_id',
        'session_year_id',
        'parent_id',
        'name',
        'discipline',
        'season',
        'starts_on',
        'ends_on',
        'gender',
        'minimum_grade',
        'maximum_grade',
        'minimum_age',
        'maximum_age',
        'active',
        'revision',
      ],
    );
    final val = ActivityGroup(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
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
          _$ActivityGroupGenderEnumEnumMap,
          v,
          unknownValue: ActivityGroupGenderEnum.unknownDefaultOpenApi,
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
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
    );
    return val;
  },
  fieldKeyMap: const {
    'schoolId': 'school_id',
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

Map<String, dynamic> _$ActivityGroupToJson(ActivityGroup instance) =>
    <String, dynamic>{
      'id': instance.id,
      'school_id': instance.schoolId,
      'session_year_id': instance.sessionYearId,
      'parent_id': instance.parentId,
      'name': instance.name,
      'discipline': instance.discipline,
      'season': instance.season,
      'starts_on': instance.startsOn,
      'ends_on': instance.endsOn,
      'gender': _$ActivityGroupGenderEnumEnumMap[instance.gender]!,
      'minimum_grade': instance.minimumGrade,
      'maximum_grade': instance.maximumGrade,
      'minimum_age': instance.minimumAge,
      'maximum_age': instance.maximumAge,
      'active': instance.active,
      'revision': instance.revision,
    };

const _$ActivityGroupGenderEnumEnumMap = {
  ActivityGroupGenderEnum.any: 'any',
  ActivityGroupGenderEnum.male: 'male',
  ActivityGroupGenderEnum.female: 'female',
  ActivityGroupGenderEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
