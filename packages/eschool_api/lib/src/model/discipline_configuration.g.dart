// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'discipline_configuration.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DisciplineConfiguration _$DisciplineConfigurationFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'DisciplineConfiguration',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'session_year_id',
        'today',
        'can_manage',
        'can_hear',
        'grade_ids',
        'grades',
        'years',
        'framework',
      ],
    );
    final val = DisciplineConfiguration(
      writable: $checkedConvert('writable', (v) => v as bool?),
      sessionYearId: $checkedConvert(
        'session_year_id',
        (v) => (v as num).toInt(),
      ),
      today: $checkedConvert('today', (v) => v as String),
      canManage: $checkedConvert('can_manage', (v) => v as bool),
      canHear: $checkedConvert('can_hear', (v) => v as bool),
      gradeIds: $checkedConvert(
        'grade_ids',
        (v) => (v as List<dynamic>).map((e) => (e as num).toInt()).toList(),
      ),
      grades: $checkedConvert(
        'grades',
        (v) => (v as List<dynamic>)
            .map((e) => DisciplineChoice.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
      years: $checkedConvert(
        'years',
        (v) => (v as List<dynamic>)
            .map((e) => DisciplineChoice.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
      framework: $checkedConvert(
        'framework',
        (v) => v == null
            ? null
            : DisciplineOptionalFramework.fromJson(v as Map<String, dynamic>),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'sessionYearId': 'session_year_id',
    'canManage': 'can_manage',
    'canHear': 'can_hear',
    'gradeIds': 'grade_ids',
  },
);

Map<String, dynamic> _$DisciplineConfigurationToJson(
  DisciplineConfiguration instance,
) => <String, dynamic>{
  'writable': ?instance.writable,
  'session_year_id': instance.sessionYearId,
  'today': instance.today,
  'can_manage': instance.canManage,
  'can_hear': instance.canHear,
  'grade_ids': instance.gradeIds,
  'grades': instance.grades.map((e) => e.toJson()).toList(),
  'years': instance.years.map((e) => e.toJson()).toList(),
  'framework': instance.framework?.toJson(),
};
