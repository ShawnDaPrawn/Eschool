// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_register_matrix_data.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicRegisterMatrixData _$AcademicRegisterMatrixDataFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'AcademicRegisterMatrixData',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'register_id',
        'label',
        'session_year_id',
        'class_section_id',
        'timetable_lesson_id',
        'on_date',
        'revision',
        'roster_hash',
        'can_write',
        'learners',
      ],
    );
    final val = AcademicRegisterMatrixData(
      registerId: $checkedConvert('register_id', (v) => (v as num).toInt()),
      label: $checkedConvert('label', (v) => v as String),
      sessionYearId: $checkedConvert(
        'session_year_id',
        (v) => (v as num).toInt(),
      ),
      classSectionId: $checkedConvert(
        'class_section_id',
        (v) => (v as num?)?.toInt(),
      ),
      timetableLessonId: $checkedConvert(
        'timetable_lesson_id',
        (v) => (v as num?)?.toInt(),
      ),
      onDate: $checkedConvert('on_date', (v) => v as String),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      rosterHash: $checkedConvert('roster_hash', (v) => v as String),
      canWrite: $checkedConvert('can_write', (v) => v as bool),
      learners: $checkedConvert(
        'learners',
        (v) => (v as List<dynamic>)
            .map(
              (e) =>
                  AcademicRegisterLearner.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'registerId': 'register_id',
    'sessionYearId': 'session_year_id',
    'classSectionId': 'class_section_id',
    'timetableLessonId': 'timetable_lesson_id',
    'onDate': 'on_date',
    'rosterHash': 'roster_hash',
    'canWrite': 'can_write',
  },
);

Map<String, dynamic> _$AcademicRegisterMatrixDataToJson(
  AcademicRegisterMatrixData instance,
) => <String, dynamic>{
  'register_id': instance.registerId,
  'label': instance.label,
  'session_year_id': instance.sessionYearId,
  'class_section_id': instance.classSectionId,
  'timetable_lesson_id': instance.timetableLessonId,
  'on_date': instance.onDate,
  'revision': instance.revision,
  'roster_hash': instance.rosterHash,
  'can_write': instance.canWrite,
  'learners': instance.learners.map((e) => e.toJson()).toList(),
};
