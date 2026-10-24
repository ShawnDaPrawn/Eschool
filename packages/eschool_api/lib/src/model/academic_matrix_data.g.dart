// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_matrix_data.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicMatrixData _$AcademicMatrixDataFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'AcademicMatrixData',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'capabilities',
        'group_id',
        'subject_id',
        'grade_level_id',
        'session_year_id',
        'term_id',
        'assessment_plan_id',
        'version',
        'current_page',
        'last_page',
        'total',
        'locked',
        'marks_lock_on',
        'tasks',
        'learners',
      ],
    );
    final val = AcademicMatrixData(
      capabilities: $checkedConvert(
        'capabilities',
        (v) => AcademicMarkCapabilities.fromJson(v as Map<String, dynamic>),
      ),
      groupId: $checkedConvert('group_id', (v) => (v as num).toInt()),
      subjectId: $checkedConvert('subject_id', (v) => (v as num).toInt()),
      gradeLevelId: $checkedConvert(
        'grade_level_id',
        (v) => (v as num).toInt(),
      ),
      sessionYearId: $checkedConvert(
        'session_year_id',
        (v) => (v as num).toInt(),
      ),
      termId: $checkedConvert('term_id', (v) => (v as num).toInt()),
      assessmentPlanId: $checkedConvert(
        'assessment_plan_id',
        (v) => (v as num).toInt(),
      ),
      version: $checkedConvert('version', (v) => (v as num).toInt()),
      currentPage: $checkedConvert('current_page', (v) => (v as num).toInt()),
      lastPage: $checkedConvert('last_page', (v) => (v as num).toInt()),
      total: $checkedConvert('total', (v) => (v as num).toInt()),
      locked: $checkedConvert('locked', (v) => v as bool),
      marksLockOn: $checkedConvert('marks_lock_on', (v) => v as String?),
      tasks: $checkedConvert(
        'tasks',
        (v) => (v as List<dynamic>)
            .map((e) => AcademicMatrixTask.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
      learners: $checkedConvert(
        'learners',
        (v) => (v as List<dynamic>)
            .map(
              (e) => AcademicMatrixLearner.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'groupId': 'group_id',
    'subjectId': 'subject_id',
    'gradeLevelId': 'grade_level_id',
    'sessionYearId': 'session_year_id',
    'termId': 'term_id',
    'assessmentPlanId': 'assessment_plan_id',
    'currentPage': 'current_page',
    'lastPage': 'last_page',
    'marksLockOn': 'marks_lock_on',
  },
);

Map<String, dynamic> _$AcademicMatrixDataToJson(AcademicMatrixData instance) =>
    <String, dynamic>{
      'capabilities': instance.capabilities.toJson(),
      'group_id': instance.groupId,
      'subject_id': instance.subjectId,
      'grade_level_id': instance.gradeLevelId,
      'session_year_id': instance.sessionYearId,
      'term_id': instance.termId,
      'assessment_plan_id': instance.assessmentPlanId,
      'version': instance.version,
      'current_page': instance.currentPage,
      'last_page': instance.lastPage,
      'total': instance.total,
      'locked': instance.locked,
      'marks_lock_on': instance.marksLockOn,
      'tasks': instance.tasks.map((e) => e.toJson()).toList(),
      'learners': instance.learners.map((e) => e.toJson()).toList(),
    };
