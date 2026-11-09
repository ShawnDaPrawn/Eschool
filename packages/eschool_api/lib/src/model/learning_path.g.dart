// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'learning_path.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

LearningPath _$LearningPathFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'LearningPath',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'school_id',
        'reader_id',
        'learner_id',
        'group_id',
        'subject_id',
        'path_id',
        'revision',
        'step',
        'mode',
        'reason',
        'topic',
        'attempt',
        'max_attempts',
        'notice',
        'message',
        'teach',
        'items',
        'check',
        'review_due_on',
        'upcoming_reviews',
        'actions',
        'server_time',
      ],
    );
    final val = LearningPath(
      schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
      readerId: $checkedConvert('reader_id', (v) => (v as num).toInt()),
      learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
      groupId: $checkedConvert('group_id', (v) => (v as num).toInt()),
      subjectId: $checkedConvert('subject_id', (v) => (v as num).toInt()),
      pathId: $checkedConvert('path_id', (v) => (v as num?)?.toInt()),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      step: $checkedConvert(
        'step',
        (v) => $enumDecode(
          _$LearningPathStepEnumEnumMap,
          v,
          unknownValue: LearningPathStepEnum.unknownDefaultOpenApi,
        ),
      ),
      mode: $checkedConvert(
        'mode',
        (v) => $enumDecode(
          _$LearningPathModeEnumEnumMap,
          v,
          unknownValue: LearningPathModeEnum.unknownDefaultOpenApi,
        ),
      ),
      reason: $checkedConvert(
        'reason',
        (v) => $enumDecodeNullable(
          _$LearningPathReasonEnumEnumMap,
          v,
          unknownValue: LearningPathReasonEnum.unknownDefaultOpenApi,
        ),
      ),
      topic: $checkedConvert(
        'topic',
        (v) => v == null
            ? null
            : LearningPathTopic.fromJson(v as Map<String, dynamic>),
      ),
      attempt: $checkedConvert('attempt', (v) => (v as num).toInt()),
      maxAttempts: $checkedConvert('max_attempts', (v) => (v as num).toInt()),
      notice: $checkedConvert(
        'notice',
        (v) => $enumDecodeNullable(
          _$LearningPathNoticeEnumEnumMap,
          v,
          unknownValue: LearningPathNoticeEnum.unknownDefaultOpenApi,
        ),
      ),
      message: $checkedConvert('message', (v) => v as String?),
      teach: $checkedConvert(
        'teach',
        (v) => v == null
            ? null
            : LearningPathTeach.fromJson(v as Map<String, dynamic>),
      ),
      items: $checkedConvert(
        'items',
        (v) => (v as List<dynamic>)
            .map((e) => LearningPathItem.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
      check: $checkedConvert(
        'check',
        (v) => v == null
            ? null
            : LearningPathCheck.fromJson(v as Map<String, dynamic>),
      ),
      reviewDueOn: $checkedConvert('review_due_on', (v) => v as String?),
      upcomingReviews: $checkedConvert(
        'upcoming_reviews',
        (v) => (v as List<dynamic>)
            .map(
              (e) => LearningPathUpcomingReviewsInner.fromJson(
                e as Map<String, dynamic>,
              ),
            )
            .toList(),
      ),
      actions: $checkedConvert(
        'actions',
        (v) => (v as List<dynamic>)
            .map(
              (e) => $enumDecode(
                _$LearningPathActionsEnumEnumMap,
                e,
                unknownValue: LearningPathActionsEnum.unknownDefaultOpenApi,
              ),
            )
            .toList(),
      ),
      serverTime: $checkedConvert(
        'server_time',
        (v) => DateTime.parse(v as String),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'schoolId': 'school_id',
    'readerId': 'reader_id',
    'learnerId': 'learner_id',
    'groupId': 'group_id',
    'subjectId': 'subject_id',
    'pathId': 'path_id',
    'maxAttempts': 'max_attempts',
    'reviewDueOn': 'review_due_on',
    'upcomingReviews': 'upcoming_reviews',
    'serverTime': 'server_time',
  },
);

Map<String, dynamic> _$LearningPathToJson(
  LearningPath instance,
) => <String, dynamic>{
  'school_id': instance.schoolId,
  'reader_id': instance.readerId,
  'learner_id': instance.learnerId,
  'group_id': instance.groupId,
  'subject_id': instance.subjectId,
  'path_id': instance.pathId,
  'revision': instance.revision,
  'step': _$LearningPathStepEnumEnumMap[instance.step]!,
  'mode': _$LearningPathModeEnumEnumMap[instance.mode]!,
  'reason': _$LearningPathReasonEnumEnumMap[instance.reason],
  'topic': instance.topic?.toJson(),
  'attempt': instance.attempt,
  'max_attempts': instance.maxAttempts,
  'notice': _$LearningPathNoticeEnumEnumMap[instance.notice],
  'message': instance.message,
  'teach': instance.teach?.toJson(),
  'items': instance.items.map((e) => e.toJson()).toList(),
  'check': instance.check?.toJson(),
  'review_due_on': instance.reviewDueOn,
  'upcoming_reviews': instance.upcomingReviews.map((e) => e.toJson()).toList(),
  'actions': instance.actions
      .map((e) => _$LearningPathActionsEnumEnumMap[e]!)
      .toList(),
  'server_time': instance.serverTime.toIso8601String(),
};

const _$LearningPathStepEnumEnumMap = {
  LearningPathStepEnum.gap: 'gap',
  LearningPathStepEnum.teach: 'teach',
  LearningPathStepEnum.practise: 'practise',
  LearningPathStepEnum.check: 'check',
  LearningPathStepEnum.review: 'review',
  LearningPathStepEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};

const _$LearningPathModeEnumEnumMap = {
  LearningPathModeEnum.learn: 'learn',
  LearningPathModeEnum.review: 'review',
  LearningPathModeEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};

const _$LearningPathReasonEnumEnumMap = {
  LearningPathReasonEnum.notYetAssessed: 'not_yet_assessed',
  LearningPathReasonEnum.prerequisiteGap: 'prerequisite_gap',
  LearningPathReasonEnum.topicGap: 'topic_gap',
  LearningPathReasonEnum.reviewDue: 'review_due',
  LearningPathReasonEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};

const _$LearningPathNoticeEnumEnumMap = {
  LearningPathNoticeEnum.passed: 'passed',
  LearningPathNoticeEnum.notYet: 'not_yet',
  LearningPathNoticeEnum.rest: 'rest',
  LearningPathNoticeEnum.caughtUp: 'caught_up',
  LearningPathNoticeEnum.nothingReady: 'nothing_ready',
  LearningPathNoticeEnum.withdrawn: 'withdrawn',
  LearningPathNoticeEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};

const _$LearningPathActionsEnumEnumMap = {
  LearningPathActionsEnum.next: 'next',
  LearningPathActionsEnum.openTutor: 'open_tutor',
  LearningPathActionsEnum.practise: 'practise',
  LearningPathActionsEnum.check: 'check',
  LearningPathActionsEnum.answer: 'answer',
  LearningPathActionsEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
