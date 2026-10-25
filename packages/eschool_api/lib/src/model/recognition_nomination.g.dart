// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'recognition_nomination.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

RecognitionNomination _$RecognitionNominationFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'RecognitionNomination',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'revision',
        'learner_id',
        'learner_name',
        'grade_level_id',
        'award_category_id',
        'award_definition_id',
        'award_name',
        'criteria',
        'reason',
        'state',
        'review_reason',
        'can_review',
        'can_withdraw',
      ],
    );
    final val = RecognitionNomination(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
      learnerName: $checkedConvert('learner_name', (v) => v as String),
      gradeLevelId: $checkedConvert(
        'grade_level_id',
        (v) => (v as num).toInt(),
      ),
      awardCategoryId: $checkedConvert(
        'award_category_id',
        (v) => (v as num).toInt(),
      ),
      awardDefinitionId: $checkedConvert(
        'award_definition_id',
        (v) => (v as num).toInt(),
      ),
      awardName: $checkedConvert('award_name', (v) => v as String),
      criteria: $checkedConvert('criteria', (v) => v as String),
      reason: $checkedConvert('reason', (v) => v as String),
      state: $checkedConvert('state', (v) => v as String),
      reviewReason: $checkedConvert('review_reason', (v) => v as String?),
      canReview: $checkedConvert('can_review', (v) => v as bool),
      canWithdraw: $checkedConvert('can_withdraw', (v) => v as bool),
    );
    return val;
  },
  fieldKeyMap: const {
    'learnerId': 'learner_id',
    'learnerName': 'learner_name',
    'gradeLevelId': 'grade_level_id',
    'awardCategoryId': 'award_category_id',
    'awardDefinitionId': 'award_definition_id',
    'awardName': 'award_name',
    'reviewReason': 'review_reason',
    'canReview': 'can_review',
    'canWithdraw': 'can_withdraw',
  },
);

Map<String, dynamic> _$RecognitionNominationToJson(
  RecognitionNomination instance,
) => <String, dynamic>{
  'id': instance.id,
  'revision': instance.revision,
  'learner_id': instance.learnerId,
  'learner_name': instance.learnerName,
  'grade_level_id': instance.gradeLevelId,
  'award_category_id': instance.awardCategoryId,
  'award_definition_id': instance.awardDefinitionId,
  'award_name': instance.awardName,
  'criteria': instance.criteria,
  'reason': instance.reason,
  'state': instance.state,
  'review_reason': instance.reviewReason,
  'can_review': instance.canReview,
  'can_withdraw': instance.canWithdraw,
};
