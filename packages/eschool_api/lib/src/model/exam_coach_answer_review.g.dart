// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'exam_coach_answer_review.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ExamCoachAnswerReview _$ExamCoachAnswerReviewFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'ExamCoachAnswerReview',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const ['id', 'mark_units', 'reason', 'answer_revision'],
    );
    final val = ExamCoachAnswerReview(
      id: $checkedConvert('id', (v) => v as String),
      markUnits: $checkedConvert('mark_units', (v) => (v as num).toInt()),
      reason: $checkedConvert('reason', (v) => v as String),
      answerRevision: $checkedConvert(
        'answer_revision',
        (v) => (v as num).toInt(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'markUnits': 'mark_units',
    'answerRevision': 'answer_revision',
  },
);

Map<String, dynamic> _$ExamCoachAnswerReviewToJson(
  ExamCoachAnswerReview instance,
) => <String, dynamic>{
  'id': instance.id,
  'mark_units': instance.markUnits,
  'reason': instance.reason,
  'answer_revision': instance.answerRevision,
};
