// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'learning_path_group_learners_inner.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

LearningPathGroupLearnersInner _$LearningPathGroupLearnersInnerFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'LearningPathGroupLearnersInner',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'learner_id',
        'display_name',
        'step',
        'step_label',
        'topic',
        'resting_topic',
        'needs_support',
        'next_review_on',
        'updated_at',
      ],
    );
    final val = LearningPathGroupLearnersInner(
      learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
      displayName: $checkedConvert('display_name', (v) => v as String),
      step: $checkedConvert(
        'step',
        (v) => $enumDecode(
          _$LearningPathGroupLearnersInnerStepEnumEnumMap,
          v,
          unknownValue:
              LearningPathGroupLearnersInnerStepEnum.unknownDefaultOpenApi,
        ),
      ),
      stepLabel: $checkedConvert('step_label', (v) => v as String),
      topic: $checkedConvert(
        'topic',
        (v) => v == null
            ? null
            : LearningPathTopic.fromJson(v as Map<String, dynamic>),
      ),
      restingTopic: $checkedConvert('resting_topic', (v) => v as String?),
      needsSupport: $checkedConvert('needs_support', (v) => v as bool),
      nextReviewOn: $checkedConvert('next_review_on', (v) => v as String?),
      updatedAt: $checkedConvert(
        'updated_at',
        (v) => v == null ? null : DateTime.parse(v as String),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'learnerId': 'learner_id',
    'displayName': 'display_name',
    'stepLabel': 'step_label',
    'restingTopic': 'resting_topic',
    'needsSupport': 'needs_support',
    'nextReviewOn': 'next_review_on',
    'updatedAt': 'updated_at',
  },
);

Map<String, dynamic> _$LearningPathGroupLearnersInnerToJson(
  LearningPathGroupLearnersInner instance,
) => <String, dynamic>{
  'learner_id': instance.learnerId,
  'display_name': instance.displayName,
  'step': _$LearningPathGroupLearnersInnerStepEnumEnumMap[instance.step]!,
  'step_label': instance.stepLabel,
  'topic': instance.topic?.toJson(),
  'resting_topic': instance.restingTopic,
  'needs_support': instance.needsSupport,
  'next_review_on': instance.nextReviewOn,
  'updated_at': instance.updatedAt?.toIso8601String(),
};

const _$LearningPathGroupLearnersInnerStepEnumEnumMap = {
  LearningPathGroupLearnersInnerStepEnum.notStarted: 'not_started',
  LearningPathGroupLearnersInnerStepEnum.gap: 'gap',
  LearningPathGroupLearnersInnerStepEnum.teach: 'teach',
  LearningPathGroupLearnersInnerStepEnum.practise: 'practise',
  LearningPathGroupLearnersInnerStepEnum.check: 'check',
  LearningPathGroupLearnersInnerStepEnum.review: 'review',
  LearningPathGroupLearnersInnerStepEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
