// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'learning_path_family_summary_subjects_inner.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

LearningPathFamilySummarySubjectsInner
_$LearningPathFamilySummarySubjectsInnerFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'LearningPathFamilySummarySubjectsInner',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'subject_id',
            'subject_name',
            'step',
            'step_label',
            'topic_name',
            'next_review_on',
            'updated_on',
          ],
        );
        final val = LearningPathFamilySummarySubjectsInner(
          subjectId: $checkedConvert('subject_id', (v) => (v as num).toInt()),
          subjectName: $checkedConvert('subject_name', (v) => v as String),
          step: $checkedConvert(
            'step',
            (v) => $enumDecode(
              _$LearningPathFamilySummarySubjectsInnerStepEnumEnumMap,
              v,
              unknownValue: LearningPathFamilySummarySubjectsInnerStepEnum
                  .unknownDefaultOpenApi,
            ),
          ),
          stepLabel: $checkedConvert('step_label', (v) => v as String),
          topicName: $checkedConvert('topic_name', (v) => v as String?),
          nextReviewOn: $checkedConvert('next_review_on', (v) => v as String?),
          updatedOn: $checkedConvert('updated_on', (v) => v as String?),
        );
        return val;
      },
      fieldKeyMap: const {
        'subjectId': 'subject_id',
        'subjectName': 'subject_name',
        'stepLabel': 'step_label',
        'topicName': 'topic_name',
        'nextReviewOn': 'next_review_on',
        'updatedOn': 'updated_on',
      },
    );

Map<String, dynamic> _$LearningPathFamilySummarySubjectsInnerToJson(
  LearningPathFamilySummarySubjectsInner instance,
) => <String, dynamic>{
  'subject_id': instance.subjectId,
  'subject_name': instance.subjectName,
  'step':
      _$LearningPathFamilySummarySubjectsInnerStepEnumEnumMap[instance.step]!,
  'step_label': instance.stepLabel,
  'topic_name': instance.topicName,
  'next_review_on': instance.nextReviewOn,
  'updated_on': instance.updatedOn,
};

const _$LearningPathFamilySummarySubjectsInnerStepEnumEnumMap = {
  LearningPathFamilySummarySubjectsInnerStepEnum.gap: 'gap',
  LearningPathFamilySummarySubjectsInnerStepEnum.teach: 'teach',
  LearningPathFamilySummarySubjectsInnerStepEnum.practise: 'practise',
  LearningPathFamilySummarySubjectsInnerStepEnum.check: 'check',
  LearningPathFamilySummarySubjectsInnerStepEnum.review: 'review',
  LearningPathFamilySummarySubjectsInnerStepEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
