// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'pathway_released_evidence.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PathwayReleasedEvidence _$PathwayReleasedEvidenceFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'PathwayReleasedEvidence',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'subject_id',
        'percentage_bp',
        'release_id',
        'grade_name',
        'provisional',
        'coverage_bp',
        'released_at',
      ],
    );
    final val = PathwayReleasedEvidence(
      subjectId: $checkedConvert('subject_id', (v) => (v as num).toInt()),
      percentageBp: $checkedConvert(
        'percentage_bp',
        (v) => (v as num?)?.toInt(),
      ),
      releaseId: $checkedConvert('release_id', (v) => (v as num).toInt()),
      gradeName: $checkedConvert('grade_name', (v) => v as String),
      provisional: $checkedConvert('provisional', (v) => v as bool),
      coverageBp: $checkedConvert('coverage_bp', (v) => (v as num).toInt()),
      releasedAt: $checkedConvert(
        'released_at',
        (v) => DateTime.parse(v as String),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'subjectId': 'subject_id',
    'percentageBp': 'percentage_bp',
    'releaseId': 'release_id',
    'gradeName': 'grade_name',
    'coverageBp': 'coverage_bp',
    'releasedAt': 'released_at',
  },
);

Map<String, dynamic> _$PathwayReleasedEvidenceToJson(
  PathwayReleasedEvidence instance,
) => <String, dynamic>{
  'subject_id': instance.subjectId,
  'percentage_bp': instance.percentageBp,
  'release_id': instance.releaseId,
  'grade_name': instance.gradeName,
  'provisional': instance.provisional,
  'coverage_bp': instance.coverageBp,
  'released_at': instance.releasedAt.toIso8601String(),
};
