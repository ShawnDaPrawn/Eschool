// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'diagnostic_attempt.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DiagnosticAttempt _$DiagnosticAttemptFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'DiagnosticAttempt',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'id',
            'learner_id',
            'group_id',
            'subject_name',
            'instructions',
            'status',
            'answered',
            'remaining',
            'question',
            'message',
            'focus_topics',
          ],
        );
        final val = DiagnosticAttempt(
          id: $checkedConvert('id', (v) => (v as num).toInt()),
          learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
          groupId: $checkedConvert('group_id', (v) => (v as num).toInt()),
          subjectName: $checkedConvert('subject_name', (v) => v as String),
          instructions: $checkedConvert('instructions', (v) => v as String),
          status: $checkedConvert(
            'status',
            (v) => $enumDecode(
              _$DiagnosticStatusEnumMap,
              v,
              unknownValue: DiagnosticStatus.unknownDefaultOpenApi,
            ),
          ),
          answered: $checkedConvert('answered', (v) => (v as num).toInt()),
          remaining: $checkedConvert('remaining', (v) => (v as num).toInt()),
          question: $checkedConvert(
            'question',
            (v) => v == null
                ? null
                : DiagnosticQuestion.fromJson(v as Map<String, dynamic>),
          ),
          message: $checkedConvert('message', (v) => v as String),
          focusTopics: $checkedConvert(
            'focus_topics',
            (v) => (v as List<dynamic>).map((e) => e as String).toList(),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'learnerId': 'learner_id',
        'groupId': 'group_id',
        'subjectName': 'subject_name',
        'focusTopics': 'focus_topics',
      },
    );

Map<String, dynamic> _$DiagnosticAttemptToJson(DiagnosticAttempt instance) =>
    <String, dynamic>{
      'id': instance.id,
      'learner_id': instance.learnerId,
      'group_id': instance.groupId,
      'subject_name': instance.subjectName,
      'instructions': instance.instructions,
      'status': _$DiagnosticStatusEnumMap[instance.status]!,
      'answered': instance.answered,
      'remaining': instance.remaining,
      'question': instance.question?.toJson(),
      'message': instance.message,
      'focus_topics': instance.focusTopics,
    };

const _$DiagnosticStatusEnumMap = {
  DiagnosticStatus.notStarted: 'not_started',
  DiagnosticStatus.inProgress: 'in_progress',
  DiagnosticStatus.completed: 'completed',
  DiagnosticStatus.unknownDefaultOpenApi: 'unknown_default_open_api',
};
