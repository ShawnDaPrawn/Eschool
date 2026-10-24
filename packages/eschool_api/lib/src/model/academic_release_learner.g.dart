// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_release_learner.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicReleaseLearner _$AcademicReleaseLearnerFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'AcademicReleaseLearner',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'learner_id',
        'snapshot_id',
        'fingerprint',
        'percentage_bp',
        'rating_level',
        'coverage_bp',
        'complete',
        'ready',
      ],
    );
    final val = AcademicReleaseLearner(
      learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
      snapshotId: $checkedConvert('snapshot_id', (v) => (v as num).toInt()),
      fingerprint: $checkedConvert('fingerprint', (v) => v as String),
      percentageBp: $checkedConvert(
        'percentage_bp',
        (v) => (v as num?)?.toInt(),
      ),
      ratingLevel: $checkedConvert('rating_level', (v) => (v as num?)?.toInt()),
      coverageBp: $checkedConvert('coverage_bp', (v) => (v as num).toInt()),
      complete: $checkedConvert('complete', (v) => v as bool),
      ready: $checkedConvert('ready', (v) => v as bool),
    );
    return val;
  },
  fieldKeyMap: const {
    'learnerId': 'learner_id',
    'snapshotId': 'snapshot_id',
    'percentageBp': 'percentage_bp',
    'ratingLevel': 'rating_level',
    'coverageBp': 'coverage_bp',
  },
);

Map<String, dynamic> _$AcademicReleaseLearnerToJson(
  AcademicReleaseLearner instance,
) => <String, dynamic>{
  'learner_id': instance.learnerId,
  'snapshot_id': instance.snapshotId,
  'fingerprint': instance.fingerprint,
  'percentage_bp': instance.percentageBp,
  'rating_level': instance.ratingLevel,
  'coverage_bp': instance.coverageBp,
  'complete': instance.complete,
  'ready': instance.ready,
};
