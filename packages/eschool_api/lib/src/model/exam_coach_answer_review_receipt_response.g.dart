// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'exam_coach_answer_review_receipt_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ExamCoachAnswerReviewReceiptResponse
_$ExamCoachAnswerReviewReceiptResponseFromJson(Map<String, dynamic> json) =>
    $checkedCreate('ExamCoachAnswerReviewReceiptResponse', json, (
      $checkedConvert,
    ) {
      $checkKeys(
        json,
        requiredKeys: const ['error', 'message', 'data', 'code'],
      );
      final val = ExamCoachAnswerReviewReceiptResponse(
        error: $checkedConvert('error', (v) => v as bool),
        message: $checkedConvert('message', (v) => v as String),
        data: $checkedConvert(
          'data',
          (v) =>
              ExamCoachAnswerReviewReceipt.fromJson(v as Map<String, dynamic>),
        ),
        code: $checkedConvert('code', (v) => (v as num).toInt()),
      );
      return val;
    });

Map<String, dynamic> _$ExamCoachAnswerReviewReceiptResponseToJson(
  ExamCoachAnswerReviewReceiptResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
