// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'learner_passport_activity.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

LearnerPassportActivity _$LearnerPassportActivityFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'LearnerPassportActivity',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'name',
        'discipline',
        'season',
        'school_year',
        'role',
        'starts_on',
        'ends_on',
      ],
    );
    final val = LearnerPassportActivity(
      name: $checkedConvert('name', (v) => v as String),
      discipline: $checkedConvert('discipline', (v) => v as String),
      season: $checkedConvert('season', (v) => v as String),
      schoolYear: $checkedConvert('school_year', (v) => v as String?),
      role: $checkedConvert(
        'role',
        (v) => $enumDecode(
          _$LearnerPassportActivityRoleEnumEnumMap,
          v,
          unknownValue: LearnerPassportActivityRoleEnum.unknownDefaultOpenApi,
        ),
      ),
      startsOn: $checkedConvert(
        'starts_on',
        (v) => DateTime.parse(v as String),
      ),
      endsOn: $checkedConvert('ends_on', (v) => DateTime.parse(v as String)),
    );
    return val;
  },
  fieldKeyMap: const {
    'schoolYear': 'school_year',
    'startsOn': 'starts_on',
    'endsOn': 'ends_on',
  },
);

Map<String, dynamic> _$LearnerPassportActivityToJson(
  LearnerPassportActivity instance,
) => <String, dynamic>{
  'name': instance.name,
  'discipline': instance.discipline,
  'season': instance.season,
  'school_year': instance.schoolYear,
  'role': _$LearnerPassportActivityRoleEnumEnumMap[instance.role]!,
  'starts_on': instance.startsOn.toIso8601String(),
  'ends_on': instance.endsOn.toIso8601String(),
};

const _$LearnerPassportActivityRoleEnumEnumMap = {
  LearnerPassportActivityRoleEnum.member: 'member',
  LearnerPassportActivityRoleEnum.captain: 'captain',
  LearnerPassportActivityRoleEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
