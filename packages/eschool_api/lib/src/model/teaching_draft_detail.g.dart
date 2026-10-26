// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'teaching_draft_detail.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TeachingDraftDetail _$TeachingDraftDetailFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'TeachingDraftDetail',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'school_id',
            'reader_id',
            'id',
            'operation_id',
            'kind',
            'state',
            'revision',
            'group_id',
            'content_version_id',
            'learner_id',
            'task_id',
            'request',
            'definition',
            'question_labels',
          ],
        );
        final val = TeachingDraftDetail(
          schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
          readerId: $checkedConvert('reader_id', (v) => (v as num).toInt()),
          id: $checkedConvert('id', (v) => v as String),
          operationId: $checkedConvert('operation_id', (v) => v as String),
          kind: $checkedConvert(
            'kind',
            (v) => $enumDecode(
              _$TeachingDraftDetailKindEnumEnumMap,
              v,
              unknownValue: TeachingDraftDetailKindEnum.unknownDefaultOpenApi,
            ),
          ),
          state: $checkedConvert(
            'state',
            (v) => $enumDecode(
              _$TeachingDraftDetailStateEnumEnumMap,
              v,
              unknownValue: TeachingDraftDetailStateEnum.unknownDefaultOpenApi,
            ),
          ),
          revision: $checkedConvert('revision', (v) => (v as num).toInt()),
          groupId: $checkedConvert('group_id', (v) => (v as num).toInt()),
          contentVersionId: $checkedConvert(
            'content_version_id',
            (v) => (v as num).toInt(),
          ),
          learnerId: $checkedConvert('learner_id', (v) => (v as num?)?.toInt()),
          taskId: $checkedConvert('task_id', (v) => (v as num?)?.toInt()),
          request: $checkedConvert(
            'request',
            (v) => v == null
                ? null
                : TeachingDraftIntent.fromJson(v as Map<String, dynamic>),
          ),
          definition: $checkedConvert(
            'definition',
            (v) => v == null
                ? null
                : TeachingDraftDefinition.fromJson(v as Map<String, dynamic>),
          ),
          questionLabels: $checkedConvert(
            'question_labels',
            (v) => (v as List<dynamic>)
                .map(
                  (e) =>
                      TeachingQuestionLabel.fromJson(e as Map<String, dynamic>),
                )
                .toList(),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'schoolId': 'school_id',
        'readerId': 'reader_id',
        'operationId': 'operation_id',
        'groupId': 'group_id',
        'contentVersionId': 'content_version_id',
        'learnerId': 'learner_id',
        'taskId': 'task_id',
        'questionLabels': 'question_labels',
      },
    );

Map<String, dynamic> _$TeachingDraftDetailToJson(
  TeachingDraftDetail instance,
) => <String, dynamic>{
  'school_id': instance.schoolId,
  'reader_id': instance.readerId,
  'id': instance.id,
  'operation_id': instance.operationId,
  'kind': _$TeachingDraftDetailKindEnumEnumMap[instance.kind]!,
  'state': _$TeachingDraftDetailStateEnumEnumMap[instance.state]!,
  'revision': instance.revision,
  'group_id': instance.groupId,
  'content_version_id': instance.contentVersionId,
  'learner_id': instance.learnerId,
  'task_id': instance.taskId,
  'request': instance.request?.toJson(),
  'definition': instance.definition?.toJson(),
  'question_labels': instance.questionLabels.map((e) => e.toJson()).toList(),
};

const _$TeachingDraftDetailKindEnumEnumMap = {
  TeachingDraftDetailKindEnum.lesson: 'lesson',
  TeachingDraftDetailKindEnum.worksheet: 'worksheet',
  TeachingDraftDetailKindEnum.practice: 'practice',
  TeachingDraftDetailKindEnum.reportComment: 'report_comment',
  TeachingDraftDetailKindEnum.scriptMarks: 'script_marks',
  TeachingDraftDetailKindEnum.reteach: 'reteach',
  TeachingDraftDetailKindEnum.followUpQuiz: 'follow_up_quiz',
  TeachingDraftDetailKindEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};

const _$TeachingDraftDetailStateEnumEnumMap = {
  TeachingDraftDetailStateEnum.pending: 'pending',
  TeachingDraftDetailStateEnum.ready: 'ready',
  TeachingDraftDetailStateEnum.approved: 'approved',
  TeachingDraftDetailStateEnum.discarded: 'discarded',
  TeachingDraftDetailStateEnum.published: 'published',
  TeachingDraftDetailStateEnum.applied: 'applied',
  TeachingDraftDetailStateEnum.failed: 'failed',
  TeachingDraftDetailStateEnum.quarantined: 'quarantined',
  TeachingDraftDetailStateEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
