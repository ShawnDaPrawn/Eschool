// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_transition_group.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicTransitionGroup _$AcademicTransitionGroupFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'AcademicTransitionGroup',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'name',
        'subject_name',
        'grade_level_id',
        'class_section_id',
      ],
    );
    final val = AcademicTransitionGroup(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      name: $checkedConvert('name', (v) => v as String),
      subjectName: $checkedConvert('subject_name', (v) => v as String),
      gradeLevelId: $checkedConvert(
        'grade_level_id',
        (v) => (v as num).toInt(),
      ),
      classSectionId: $checkedConvert(
        'class_section_id',
        (v) => (v as num?)?.toInt(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'subjectName': 'subject_name',
    'gradeLevelId': 'grade_level_id',
    'classSectionId': 'class_section_id',
  },
);

Map<String, dynamic> _$AcademicTransitionGroupToJson(
  AcademicTransitionGroup instance,
) => <String, dynamic>{
  'id': instance.id,
  'name': instance.name,
  'subject_name': instance.subjectName,
  'grade_level_id': instance.gradeLevelId,
  'class_section_id': instance.classSectionId,
};
