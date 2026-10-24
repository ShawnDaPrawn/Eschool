// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_transition_configuration.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicTransitionConfiguration _$AcademicTransitionConfigurationFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'AcademicTransitionConfiguration',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'session_year_id',
        'school_years',
        'grades',
        'homerooms',
        'groups',
        'retention',
      ],
    );
    final val = AcademicTransitionConfiguration(
      sessionYearId: $checkedConvert(
        'session_year_id',
        (v) => (v as num).toInt(),
      ),
      schoolYears: $checkedConvert(
        'school_years',
        (v) => (v as List<dynamic>)
            .map(
              (e) => AcademicTransitionYear.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
      ),
      grades: $checkedConvert(
        'grades',
        (v) => (v as List<dynamic>)
            .map(
              (e) =>
                  AcademicTransitionGrade.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
      ),
      homerooms: $checkedConvert(
        'homerooms',
        (v) => (v as List<dynamic>)
            .map(
              (e) => AcademicTransitionHomeroom.fromJson(
                e as Map<String, dynamic>,
              ),
            )
            .toList(),
      ),
      groups: $checkedConvert(
        'groups',
        (v) => (v as List<dynamic>)
            .map(
              (e) =>
                  AcademicTransitionGroup.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
      ),
      retention: $checkedConvert(
        'retention',
        (v) => AcademicTransitionRetention.fromJson(v as Map<String, dynamic>),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'sessionYearId': 'session_year_id',
    'schoolYears': 'school_years',
  },
);

Map<String, dynamic> _$AcademicTransitionConfigurationToJson(
  AcademicTransitionConfiguration instance,
) => <String, dynamic>{
  'session_year_id': instance.sessionYearId,
  'school_years': instance.schoolYears.map((e) => e.toJson()).toList(),
  'grades': instance.grades.map((e) => e.toJson()).toList(),
  'homerooms': instance.homerooms.map((e) => e.toJson()).toList(),
  'groups': instance.groups.map((e) => e.toJson()).toList(),
  'retention': instance.retention.toJson(),
};
