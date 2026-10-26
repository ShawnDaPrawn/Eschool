// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'exam_coach_answer_review_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ExamCoachAnswerReviewWrite _$ExamCoachAnswerReviewWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'ExamCoachAnswerReviewWrite',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'operation_id',
        'expected_revision',
        'mark_units',
        'reason',
      ],
    );
    final val = ExamCoachAnswerReviewWrite(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      expectedRevision: $checkedConvert(
        'expected_revision',
        (v) => (v as num).toInt(),
      ),
      markUnits: $checkedConvert('mark_units', (v) => (v as num).toInt()),
      reason: $checkedConvert('reason', (v) => v as String),
    );
    return val;
  },
  fieldKeyMap: const {
    'operationId': 'operation_id',
    'expectedRevision': 'expected_revision',
    'markUnits': 'mark_units',
  },
);

Map<String, dynamic> _$ExamCoachAnswerReviewWriteToJson(
  ExamCoachAnswerReviewWrite instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'expected_revision': instance.expectedRevision,
  'mark_units': instance.markUnits,
  'reason': instance.reason,
};
