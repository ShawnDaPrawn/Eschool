// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_released_result.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicReleasedResult _$AcademicReleasedResultFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'AcademicReleasedResult',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'release_id',
        'subject_id',
        'grade_level_id',
        'session_year_id',
        'term_id',
        'mode',
        'provisional',
        'percentage_bp',
        'rating_level',
        'coverage_bp',
        'released_at',
      ],
    );
    final val = AcademicReleasedResult(
      subjectName: $checkedConvert('subject_name', (v) => v as String?),
      gradeName: $checkedConvert('grade_name', (v) => v as String?),
      releaseId: $checkedConvert('release_id', (v) => (v as num).toInt()),
      subjectId: $checkedConvert('subject_id', (v) => (v as num).toInt()),
      gradeLevelId: $checkedConvert(
        'grade_level_id',
        (v) => (v as num).toInt(),
      ),
      sessionYearId: $checkedConvert(
        'session_year_id',
        (v) => (v as num).toInt(),
      ),
      termId: $checkedConvert('term_id', (v) => (v as num?)?.toInt()),
      mode: $checkedConvert('mode', (v) => v as String),
      provisional: $checkedConvert('provisional', (v) => v as bool),
      percentageBp: $checkedConvert(
        'percentage_bp',
        (v) => (v as num?)?.toInt(),
      ),
      ratingLevel: $checkedConvert('rating_level', (v) => (v as num?)?.toInt()),
      coverageBp: $checkedConvert('coverage_bp', (v) => (v as num).toInt()),
      releasedAt: $checkedConvert('released_at', (v) => v as String),
    );
    return val;
  },
  fieldKeyMap: const {
    'subjectName': 'subject_name',
    'gradeName': 'grade_name',
    'releaseId': 'release_id',
    'subjectId': 'subject_id',
    'gradeLevelId': 'grade_level_id',
    'sessionYearId': 'session_year_id',
    'termId': 'term_id',
    'percentageBp': 'percentage_bp',
    'ratingLevel': 'rating_level',
    'coverageBp': 'coverage_bp',
    'releasedAt': 'released_at',
  },
);

Map<String, dynamic> _$AcademicReleasedResultToJson(
  AcademicReleasedResult instance,
) => <String, dynamic>{
  'subject_name': ?instance.subjectName,
  'grade_name': ?instance.gradeName,
  'release_id': instance.releaseId,
  'subject_id': instance.subjectId,
  'grade_level_id': instance.gradeLevelId,
  'session_year_id': instance.sessionYearId,
  'term_id': instance.termId,
  'mode': instance.mode,
  'provisional': instance.provisional,
  'percentage_bp': instance.percentageBp,
  'rating_level': instance.ratingLevel,
  'coverage_bp': instance.coverageBp,
  'released_at': instance.releasedAt,
};
