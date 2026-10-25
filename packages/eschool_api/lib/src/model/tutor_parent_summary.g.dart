// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'tutor_parent_summary.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TutorParentSummary _$TutorParentSummaryFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'TutorParentSummary',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'school_id',
            'reader_id',
            'learner_id',
            'subject_id',
            'subject_name',
            'learning_sessions',
            'learning_steps',
            'suggestion_key',
            'contains_raw_chat',
          ],
        );
        final val = TutorParentSummary(
          schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
          readerId: $checkedConvert('reader_id', (v) => (v as num).toInt()),
          learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
          subjectId: $checkedConvert('subject_id', (v) => (v as num).toInt()),
          subjectName: $checkedConvert('subject_name', (v) => v as String),
          learningSessions: $checkedConvert(
            'learning_sessions',
            (v) => (v as num).toInt(),
          ),
          learningSteps: $checkedConvert(
            'learning_steps',
            (v) => (v as num).toInt(),
          ),
          suggestionKey: $checkedConvert('suggestion_key', (v) => v as String),
          containsRawChat: $checkedConvert(
            'contains_raw_chat',
            (v) => v as bool,
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'schoolId': 'school_id',
        'readerId': 'reader_id',
        'learnerId': 'learner_id',
        'subjectId': 'subject_id',
        'subjectName': 'subject_name',
        'learningSessions': 'learning_sessions',
        'learningSteps': 'learning_steps',
        'suggestionKey': 'suggestion_key',
        'containsRawChat': 'contains_raw_chat',
      },
    );

Map<String, dynamic> _$TutorParentSummaryToJson(TutorParentSummary instance) =>
    <String, dynamic>{
      'school_id': instance.schoolId,
      'reader_id': instance.readerId,
      'learner_id': instance.learnerId,
      'subject_id': instance.subjectId,
      'subject_name': instance.subjectName,
      'learning_sessions': instance.learningSessions,
      'learning_steps': instance.learningSteps,
      'suggestion_key': instance.suggestionKey,
      'contains_raw_chat': instance.containsRawChat,
    };
