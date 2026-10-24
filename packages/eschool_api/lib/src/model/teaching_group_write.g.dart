// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'teaching_group_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TeachingGroupWrite _$TeachingGroupWriteFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'TeachingGroupWrite',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'name',
            'subject_id',
            'grade_level_id',
            'session_year_id',
            'active',
          ],
        );
        final val = TeachingGroupWrite(
          name: $checkedConvert('name', (v) => v as String),
          subjectId: $checkedConvert('subject_id', (v) => (v as num).toInt()),
          gradeLevelId: $checkedConvert(
            'grade_level_id',
            (v) => (v as num).toInt(),
          ),
          sessionYearId: $checkedConvert(
            'session_year_id',
            (v) => (v as num).toInt(),
          ),
          classSectionId: $checkedConvert(
            'class_section_id',
            (v) => (v as num?)?.toInt(),
          ),
          active: $checkedConvert('active', (v) => v as bool),
          revision: $checkedConvert('revision', (v) => (v as num?)?.toInt()),
        );
        return val;
      },
      fieldKeyMap: const {
        'subjectId': 'subject_id',
        'gradeLevelId': 'grade_level_id',
        'sessionYearId': 'session_year_id',
        'classSectionId': 'class_section_id',
      },
    );

Map<String, dynamic> _$TeachingGroupWriteToJson(TeachingGroupWrite instance) =>
    <String, dynamic>{
      'name': instance.name,
      'subject_id': instance.subjectId,
      'grade_level_id': instance.gradeLevelId,
      'session_year_id': instance.sessionYearId,
      'class_section_id': ?instance.classSectionId,
      'active': instance.active,
      'revision': ?instance.revision,
    };
