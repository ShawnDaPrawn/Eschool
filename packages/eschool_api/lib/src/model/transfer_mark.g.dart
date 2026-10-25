// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'transfer_mark.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TransferMark _$TransferMarkFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'TransferMark',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'release_id',
        'release_version',
        'subject_name',
        'grade_level_id',
        'session_year_id',
        'term_id',
        'mode',
        'percentage_bp',
        'rating_level',
        'coverage_bp',
        'fingerprint',
        'released_at',
      ],
    );
    final val = TransferMark(
      releaseId: $checkedConvert('release_id', (v) => (v as num).toInt()),
      releaseVersion: $checkedConvert(
        'release_version',
        (v) => (v as num).toInt(),
      ),
      subjectName: $checkedConvert('subject_name', (v) => v as String),
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
      percentageBp: $checkedConvert(
        'percentage_bp',
        (v) => (v as num?)?.toInt(),
      ),
      ratingLevel: $checkedConvert('rating_level', (v) => (v as num?)?.toInt()),
      coverageBp: $checkedConvert('coverage_bp', (v) => (v as num?)?.toInt()),
      fingerprint: $checkedConvert('fingerprint', (v) => v as String),
      releasedAt: $checkedConvert(
        'released_at',
        (v) => DateTime.parse(v as String),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'releaseId': 'release_id',
    'releaseVersion': 'release_version',
    'subjectName': 'subject_name',
    'gradeLevelId': 'grade_level_id',
    'sessionYearId': 'session_year_id',
    'termId': 'term_id',
    'percentageBp': 'percentage_bp',
    'ratingLevel': 'rating_level',
    'coverageBp': 'coverage_bp',
    'releasedAt': 'released_at',
  },
);

Map<String, dynamic> _$TransferMarkToJson(TransferMark instance) =>
    <String, dynamic>{
      'release_id': instance.releaseId,
      'release_version': instance.releaseVersion,
      'subject_name': instance.subjectName,
      'grade_level_id': instance.gradeLevelId,
      'session_year_id': instance.sessionYearId,
      'term_id': instance.termId,
      'mode': instance.mode,
      'percentage_bp': instance.percentageBp,
      'rating_level': instance.ratingLevel,
      'coverage_bp': instance.coverageBp,
      'fingerprint': instance.fingerprint,
      'released_at': instance.releasedAt.toIso8601String(),
    };
