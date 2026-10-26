// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'exam_coach_answer_feedback_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ExamCoachAnswerFeedbackWrite _$ExamCoachAnswerFeedbackWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ExamCoachAnswerFeedbackWrite', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['operation_id', 'working']);
  final val = ExamCoachAnswerFeedbackWrite(
    operationId: $checkedConvert('operation_id', (v) => v as String),
    working: $checkedConvert('working', (v) => v as String?),
  );
  return val;
}, fieldKeyMap: const {'operationId': 'operation_id'});

Map<String, dynamic> _$ExamCoachAnswerFeedbackWriteToJson(
  ExamCoachAnswerFeedbackWrite instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'working': instance.working,
};
