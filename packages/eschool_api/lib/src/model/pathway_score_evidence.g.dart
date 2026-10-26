// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'pathway_score_evidence.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PathwayScoreEvidence _$PathwayScoreEvidenceFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'PathwayScoreEvidence',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'subject_id',
        'percentage_bp',
        'points',
        'minimum_bp',
        'counts_for_score',
      ],
    );
    final val = PathwayScoreEvidence(
      subjectId: $checkedConvert('subject_id', (v) => (v as num).toInt()),
      percentageBp: $checkedConvert(
        'percentage_bp',
        (v) => (v as num?)?.toInt(),
      ),
      points: $checkedConvert('points', (v) => (v as num?)?.toInt()),
      minimumBp: $checkedConvert('minimum_bp', (v) => (v as num?)?.toInt()),
      countsForScore: $checkedConvert('counts_for_score', (v) => v as bool),
    );
    return val;
  },
  fieldKeyMap: const {
    'subjectId': 'subject_id',
    'percentageBp': 'percentage_bp',
    'minimumBp': 'minimum_bp',
    'countsForScore': 'counts_for_score',
  },
);

Map<String, dynamic> _$PathwayScoreEvidenceToJson(
  PathwayScoreEvidence instance,
) => <String, dynamic>{
  'subject_id': instance.subjectId,
  'percentage_bp': instance.percentageBp,
  'points': instance.points,
  'minimum_bp': instance.minimumBp,
  'counts_for_score': instance.countsForScore,
};
