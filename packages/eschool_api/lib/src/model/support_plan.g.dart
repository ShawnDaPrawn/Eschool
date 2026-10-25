// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'support_plan.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SupportPlan _$SupportPlanFromJson(Map<String, dynamic> json) => $checkedCreate(
  'SupportPlan',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'learner_id',
        'learner_name',
        'revision',
        'state',
        'review_due_on',
        'started_on',
        'sias_stage',
        'needs',
        'accommodations',
        'reviews_has_more',
        'disclosures_has_more',
        'reviews',
        'disclosures',
      ],
    );
    final val = SupportPlan(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
      learnerName: $checkedConvert('learner_name', (v) => v as String),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      state: $checkedConvert(
        'state',
        (v) => $enumDecode(
          _$SupportPlanStateEnumEnumMap,
          v,
          unknownValue: SupportPlanStateEnum.unknownDefaultOpenApi,
        ),
      ),
      reviewDueOn: $checkedConvert('review_due_on', (v) => v as String?),
      startedOn: $checkedConvert('started_on', (v) => v as String),
      siasStage: $checkedConvert('sias_stage', (v) => v as String),
      needs: $checkedConvert('needs', (v) => v as String),
      accommodations: $checkedConvert('accommodations', (v) => v as String),
      reviewsHasMore: $checkedConvert('reviews_has_more', (v) => v as bool),
      disclosuresHasMore: $checkedConvert(
        'disclosures_has_more',
        (v) => v as bool,
      ),
      reviews: $checkedConvert(
        'reviews',
        (v) => (v as List<dynamic>)
            .map((e) => SupportReview.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
      disclosures: $checkedConvert(
        'disclosures',
        (v) => (v as List<dynamic>)
            .map(
              (e) =>
                  SupportDisclosureSummary.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'learnerId': 'learner_id',
    'learnerName': 'learner_name',
    'reviewDueOn': 'review_due_on',
    'startedOn': 'started_on',
    'siasStage': 'sias_stage',
    'reviewsHasMore': 'reviews_has_more',
    'disclosuresHasMore': 'disclosures_has_more',
  },
);

Map<String, dynamic> _$SupportPlanToJson(SupportPlan instance) =>
    <String, dynamic>{
      'id': instance.id,
      'learner_id': instance.learnerId,
      'learner_name': instance.learnerName,
      'revision': instance.revision,
      'state': _$SupportPlanStateEnumEnumMap[instance.state]!,
      'review_due_on': instance.reviewDueOn,
      'started_on': instance.startedOn,
      'sias_stage': instance.siasStage,
      'needs': instance.needs,
      'accommodations': instance.accommodations,
      'reviews_has_more': instance.reviewsHasMore,
      'disclosures_has_more': instance.disclosuresHasMore,
      'reviews': instance.reviews.map((e) => e.toJson()).toList(),
      'disclosures': instance.disclosures.map((e) => e.toJson()).toList(),
    };

const _$SupportPlanStateEnumEnumMap = {
  SupportPlanStateEnum.active: 'active',
  SupportPlanStateEnum.closed: 'closed',
  SupportPlanStateEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
