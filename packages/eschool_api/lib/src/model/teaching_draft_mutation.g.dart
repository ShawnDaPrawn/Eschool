// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'teaching_draft_mutation.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TeachingDraftMutation _$TeachingDraftMutationFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'TeachingDraftMutation',
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
        'write_operation_id',
      ],
    );
    final val = TeachingDraftMutation(
      schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
      readerId: $checkedConvert('reader_id', (v) => (v as num).toInt()),
      id: $checkedConvert('id', (v) => v as String),
      operationId: $checkedConvert('operation_id', (v) => v as String),
      kind: $checkedConvert(
        'kind',
        (v) => $enumDecode(
          _$TeachingDraftMutationKindEnumEnumMap,
          v,
          unknownValue: TeachingDraftMutationKindEnum.unknownDefaultOpenApi,
        ),
      ),
      state: $checkedConvert(
        'state',
        (v) => $enumDecode(
          _$TeachingDraftMutationStateEnumEnumMap,
          v,
          unknownValue: TeachingDraftMutationStateEnum.unknownDefaultOpenApi,
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
      writeOperationId: $checkedConvert(
        'write_operation_id',
        (v) => v as String,
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
    'writeOperationId': 'write_operation_id',
  },
);

Map<String, dynamic> _$TeachingDraftMutationToJson(
  TeachingDraftMutation instance,
) => <String, dynamic>{
  'school_id': instance.schoolId,
  'reader_id': instance.readerId,
  'id': instance.id,
  'operation_id': instance.operationId,
  'kind': _$TeachingDraftMutationKindEnumEnumMap[instance.kind]!,
  'state': _$TeachingDraftMutationStateEnumEnumMap[instance.state]!,
  'revision': instance.revision,
  'group_id': instance.groupId,
  'content_version_id': instance.contentVersionId,
  'learner_id': instance.learnerId,
  'task_id': instance.taskId,
  'write_operation_id': instance.writeOperationId,
};

const _$TeachingDraftMutationKindEnumEnumMap = {
  TeachingDraftMutationKindEnum.lesson: 'lesson',
  TeachingDraftMutationKindEnum.worksheet: 'worksheet',
  TeachingDraftMutationKindEnum.practice: 'practice',
  TeachingDraftMutationKindEnum.reportComment: 'report_comment',
  TeachingDraftMutationKindEnum.scriptMarks: 'script_marks',
  TeachingDraftMutationKindEnum.reteach: 'reteach',
  TeachingDraftMutationKindEnum.followUpQuiz: 'follow_up_quiz',
  TeachingDraftMutationKindEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$TeachingDraftMutationStateEnumEnumMap = {
  TeachingDraftMutationStateEnum.pending: 'pending',
  TeachingDraftMutationStateEnum.ready: 'ready',
  TeachingDraftMutationStateEnum.approved: 'approved',
  TeachingDraftMutationStateEnum.discarded: 'discarded',
  TeachingDraftMutationStateEnum.published: 'published',
  TeachingDraftMutationStateEnum.applied: 'applied',
  TeachingDraftMutationStateEnum.failed: 'failed',
  TeachingDraftMutationStateEnum.quarantined: 'quarantined',
  TeachingDraftMutationStateEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
