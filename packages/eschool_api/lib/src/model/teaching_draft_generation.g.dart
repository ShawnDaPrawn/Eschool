// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'teaching_draft_generation.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TeachingDraftGeneration _$TeachingDraftGenerationFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'TeachingDraftGeneration',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'operation_id',
        'content_version_id',
        'kind',
        'language',
        'brief',
        'learner_id',
        'task_id',
        'photo_ids',
      ],
    );
    final val = TeachingDraftGeneration(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      contentVersionId: $checkedConvert(
        'content_version_id',
        (v) => (v as num).toInt(),
      ),
      kind: $checkedConvert(
        'kind',
        (v) => $enumDecode(
          _$TeachingDraftGenerationKindEnumEnumMap,
          v,
          unknownValue: TeachingDraftGenerationKindEnum.unknownDefaultOpenApi,
        ),
      ),
      language: $checkedConvert(
        'language',
        (v) => $enumDecode(
          _$TeachingDraftGenerationLanguageEnumEnumMap,
          v,
          unknownValue:
              TeachingDraftGenerationLanguageEnum.unknownDefaultOpenApi,
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
        (v) => (v as List<dynamic>?)?.map((e) => (e as num).toInt()).toSet(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'operationId': 'operation_id',
    'contentVersionId': 'content_version_id',
    'learnerId': 'learner_id',
    'taskId': 'task_id',
    'photoIds': 'photo_ids',
    'topicIds': 'topic_ids',
  },
);

Map<String, dynamic> _$TeachingDraftGenerationToJson(
  TeachingDraftGeneration instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'content_version_id': instance.contentVersionId,
  'kind': _$TeachingDraftGenerationKindEnumEnumMap[instance.kind]!,
  'language': _$TeachingDraftGenerationLanguageEnumEnumMap[instance.language]!,
  'brief': instance.brief,
  'learner_id': instance.learnerId,
  'task_id': instance.taskId,
  'photo_ids': instance.photoIds.toList(),
  'topic_ids': ?instance.topicIds?.toList(),
};

const _$TeachingDraftGenerationKindEnumEnumMap = {
  TeachingDraftGenerationKindEnum.lesson: 'lesson',
  TeachingDraftGenerationKindEnum.worksheet: 'worksheet',
  TeachingDraftGenerationKindEnum.practice: 'practice',
  TeachingDraftGenerationKindEnum.reportComment: 'report_comment',
  TeachingDraftGenerationKindEnum.scriptMarks: 'script_marks',
  TeachingDraftGenerationKindEnum.reteach: 'reteach',
  TeachingDraftGenerationKindEnum.followUpQuiz: 'follow_up_quiz',
  TeachingDraftGenerationKindEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$TeachingDraftGenerationLanguageEnumEnumMap = {
  TeachingDraftGenerationLanguageEnum.en: 'en',
  TeachingDraftGenerationLanguageEnum.af: 'af',
  TeachingDraftGenerationLanguageEnum.zu: 'zu',
  TeachingDraftGenerationLanguageEnum.ar: 'ar',
  TeachingDraftGenerationLanguageEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
