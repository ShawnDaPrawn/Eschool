// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'teaching_draft_summary.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TeachingDraftSummary _$TeachingDraftSummaryFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'TeachingDraftSummary',
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
      ],
    );
    final val = TeachingDraftSummary(
      schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
      readerId: $checkedConvert('reader_id', (v) => (v as num).toInt()),
      id: $checkedConvert('id', (v) => v as String),
      operationId: $checkedConvert('operation_id', (v) => v as String),
      kind: $checkedConvert(
        'kind',
        (v) => $enumDecode(
          _$TeachingDraftSummaryKindEnumEnumMap,
          v,
          unknownValue: TeachingDraftSummaryKindEnum.unknownDefaultOpenApi,
        ),
      ),
      state: $checkedConvert(
        'state',
        (v) => $enumDecode(
          _$TeachingDraftSummaryStateEnumEnumMap,
          v,
          unknownValue: TeachingDraftSummaryStateEnum.unknownDefaultOpenApi,
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
  },
);

Map<String, dynamic> _$TeachingDraftSummaryToJson(
  TeachingDraftSummary instance,
) => <String, dynamic>{
  'school_id': instance.schoolId,
  'reader_id': instance.readerId,
  'id': instance.id,
  'operation_id': instance.operationId,
  'kind': _$TeachingDraftSummaryKindEnumEnumMap[instance.kind]!,
  'state': _$TeachingDraftSummaryStateEnumEnumMap[instance.state]!,
  'revision': instance.revision,
  'group_id': instance.groupId,
  'content_version_id': instance.contentVersionId,
  'learner_id': instance.learnerId,
  'task_id': instance.taskId,
};

const _$TeachingDraftSummaryKindEnumEnumMap = {
  TeachingDraftSummaryKindEnum.lesson: 'lesson',
  TeachingDraftSummaryKindEnum.worksheet: 'worksheet',
  TeachingDraftSummaryKindEnum.practice: 'practice',
  TeachingDraftSummaryKindEnum.reportComment: 'report_comment',
  TeachingDraftSummaryKindEnum.scriptMarks: 'script_marks',
  TeachingDraftSummaryKindEnum.reteach: 'reteach',
  TeachingDraftSummaryKindEnum.followUpQuiz: 'follow_up_quiz',
  TeachingDraftSummaryKindEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$TeachingDraftSummaryStateEnumEnumMap = {
  TeachingDraftSummaryStateEnum.pending: 'pending',
  TeachingDraftSummaryStateEnum.ready: 'ready',
  TeachingDraftSummaryStateEnum.approved: 'approved',
  TeachingDraftSummaryStateEnum.discarded: 'discarded',
  TeachingDraftSummaryStateEnum.published: 'published',
  TeachingDraftSummaryStateEnum.applied: 'applied',
  TeachingDraftSummaryStateEnum.failed: 'failed',
  TeachingDraftSummaryStateEnum.quarantined: 'quarantined',
  TeachingDraftSummaryStateEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
