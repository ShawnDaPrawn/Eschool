// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'exam_coach_answer_feedback.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ExamCoachAnswerFeedback _$ExamCoachAnswerFeedbackFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'ExamCoachAnswerFeedback',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'question_token',
        'mark_units',
        'confidence_basis_points',
        'feedback',
      ],
    );
    final val = ExamCoachAnswerFeedback(
      questionToken: $checkedConvert('question_token', (v) => v as String),
      markUnits: $checkedConvert('mark_units', (v) => (v as num).toInt()),
      confidenceBasisPoints: $checkedConvert(
        'confidence_basis_points',
        (v) => (v as num).toInt(),
      ),
      feedback: $checkedConvert('feedback', (v) => v as String),
    );
    return val;
  },
  fieldKeyMap: const {
    'questionToken': 'question_token',
    'markUnits': 'mark_units',
    'confidenceBasisPoints': 'confidence_basis_points',
  },
);

Map<String, dynamic> _$ExamCoachAnswerFeedbackToJson(
  ExamCoachAnswerFeedback instance,
) => <String, dynamic>{
  'question_token': instance.questionToken,
  'mark_units': instance.markUnits,
  'confidence_basis_points': instance.confidenceBasisPoints,
  'feedback': instance.feedback,
};
