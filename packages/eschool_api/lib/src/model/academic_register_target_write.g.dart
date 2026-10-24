// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_register_target_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicRegisterTargetWrite _$AcademicRegisterTargetWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'AcademicRegisterTargetWrite',
  json,
  ($checkedConvert) {
    $checkKeys(json, requiredKeys: const ['session_year_id', 'on_date']);
    final val = AcademicRegisterTargetWrite(
      sessionYearId: $checkedConvert(
        'session_year_id',
        (v) => (v as num).toInt(),
      ),
      onDate: $checkedConvert('on_date', (v) => v as String),
      classSectionId: $checkedConvert(
        'class_section_id',
        (v) => (v as num?)?.toInt(),
      ),
      timetableLessonId: $checkedConvert(
        'timetable_lesson_id',
        (v) => (v as num?)?.toInt(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'sessionYearId': 'session_year_id',
    'onDate': 'on_date',
    'classSectionId': 'class_section_id',
    'timetableLessonId': 'timetable_lesson_id',
  },
);

Map<String, dynamic> _$AcademicRegisterTargetWriteToJson(
  AcademicRegisterTargetWrite instance,
) => <String, dynamic>{
  'session_year_id': instance.sessionYearId,
  'on_date': instance.onDate,
  'class_section_id': ?instance.classSectionId,
  'timetable_lesson_id': ?instance.timetableLessonId,
};
