// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_release_data.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicReleaseData _$AcademicReleaseDataFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'AcademicReleaseData',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'id',
            'subject_id',
            'grade_level_id',
            'session_year_id',
            'term_id',
            'mode',
            'provisional',
            'version',
            'fingerprint',
            'released_at',
            'learner_ids',
          ],
        );
        final val = AcademicReleaseData(
          id: $checkedConvert('id', (v) => (v as num).toInt()),
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
          version: $checkedConvert('version', (v) => (v as num).toInt()),
          fingerprint: $checkedConvert('fingerprint', (v) => v as String),
          releasedAt: $checkedConvert('released_at', (v) => v as String),
          learnerIds: $checkedConvert(
            'learner_ids',
            (v) => (v as List<dynamic>).map((e) => (e as num).toInt()).toList(),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'subjectId': 'subject_id',
        'gradeLevelId': 'grade_level_id',
        'sessionYearId': 'session_year_id',
        'termId': 'term_id',
        'releasedAt': 'released_at',
        'learnerIds': 'learner_ids',
      },
    );

Map<String, dynamic> _$AcademicReleaseDataToJson(
  AcademicReleaseData instance,
) => <String, dynamic>{
  'id': instance.id,
  'subject_id': instance.subjectId,
  'grade_level_id': instance.gradeLevelId,
  'session_year_id': instance.sessionYearId,
  'term_id': instance.termId,
  'mode': instance.mode,
  'provisional': instance.provisional,
  'version': instance.version,
  'fingerprint': instance.fingerprint,
  'released_at': instance.releasedAt,
  'learner_ids': instance.learnerIds,
};
