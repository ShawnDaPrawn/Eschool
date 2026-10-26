// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'exam_coach_answer_review_receipt.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ExamCoachAnswerReviewReceipt _$ExamCoachAnswerReviewReceiptFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ExamCoachAnswerReviewReceipt', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['id', 'answer_id', 'revision', 'mark_units'],
  );
  final val = ExamCoachAnswerReviewReceipt(
    id: $checkedConvert('id', (v) => v as String),
    answerId: $checkedConvert('answer_id', (v) => v as String),
    revision: $checkedConvert('revision', (v) => (v as num).toInt()),
    markUnits: $checkedConvert('mark_units', (v) => (v as num).toInt()),
  );
  return val;
}, fieldKeyMap: const {'answerId': 'answer_id', 'markUnits': 'mark_units'});

Map<String, dynamic> _$ExamCoachAnswerReviewReceiptToJson(
  ExamCoachAnswerReviewReceipt instance,
) => <String, dynamic>{
  'id': instance.id,
  'answer_id': instance.answerId,
  'revision': instance.revision,
  'mark_units': instance.markUnits,
};
