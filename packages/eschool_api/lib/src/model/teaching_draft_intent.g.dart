// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'teaching_draft_intent.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TeachingDraftIntent _$TeachingDraftIntentFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'TeachingDraftIntent',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'kind',
            'language',
            'brief',
            'learner_id',
            'task_id',
            'photo_ids',
            'topic_ids',
          ],
        );
        final val = TeachingDraftIntent(
          kind: $checkedConvert(
            'kind',
            (v) => $enumDecode(
              _$TeachingDraftIntentKindEnumEnumMap,
              v,
              unknownValue: TeachingDraftIntentKindEnum.unknownDefaultOpenApi,
            ),
          ),
          language: $checkedConvert(
            'language',
            (v) => $enumDecode(
              _$TeachingDraftIntentLanguageEnumEnumMap,
              v,
              unknownValue:
                  TeachingDraftIntentLanguageEnum.unknownDefaultOpenApi,
            ),
          ),
          brief: $checkedConvert('brief', (v) => v as String),
          learnerId: $checkedConvert('learner_id', (v) => (v as num?)?.toInt()),
          taskId: $checkedConvert('task_id', (v) => (v as num?)?.toInt()),
          photoIds: $checkedConvert(
            'photo_ids',
            (v) => (v as List<dynamic>).map((e) => e as String).toSet(),
          ),
          topicIds: $checkedConvert(
            'topic_ids',
            (v) => (v as List<dynamic>).map((e) => (e as num).toInt()).toSet(),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'learnerId': 'learner_id',
        'taskId': 'task_id',
        'photoIds': 'photo_ids',
        'topicIds': 'topic_ids',
      },
    );

Map<String, dynamic> _$TeachingDraftIntentToJson(
  TeachingDraftIntent instance,
) => <String, dynamic>{
  'kind': _$TeachingDraftIntentKindEnumEnumMap[instance.kind]!,
  'language': _$TeachingDraftIntentLanguageEnumEnumMap[instance.language]!,
  'brief': instance.brief,
  'learner_id': instance.learnerId,
  'task_id': instance.taskId,
  'photo_ids': instance.photoIds.toList(),
  'topic_ids': instance.topicIds.toList(),
};

const _$TeachingDraftIntentKindEnumEnumMap = {
  TeachingDraftIntentKindEnum.lesson: 'lesson',
  TeachingDraftIntentKindEnum.worksheet: 'worksheet',
  TeachingDraftIntentKindEnum.practice: 'practice',
  TeachingDraftIntentKindEnum.reportComment: 'report_comment',
  TeachingDraftIntentKindEnum.scriptMarks: 'script_marks',
  TeachingDraftIntentKindEnum.reteach: 'reteach',
  TeachingDraftIntentKindEnum.followUpQuiz: 'follow_up_quiz',
  TeachingDraftIntentKindEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};

const _$TeachingDraftIntentLanguageEnumEnumMap = {
  TeachingDraftIntentLanguageEnum.en: 'en',
  TeachingDraftIntentLanguageEnum.af: 'af',
  TeachingDraftIntentLanguageEnum.zu: 'zu',
  TeachingDraftIntentLanguageEnum.ar: 'ar',
  TeachingDraftIntentLanguageEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
