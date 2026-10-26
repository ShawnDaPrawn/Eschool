// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'exam_coach_answer_upload_receipt_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ExamCoachAnswerUploadReceiptResponse
_$ExamCoachAnswerUploadReceiptResponseFromJson(Map<String, dynamic> json) =>
    $checkedCreate('ExamCoachAnswerUploadReceiptResponse', json, (
      $checkedConvert,
    ) {
      $checkKeys(
        json,
        requiredKeys: const ['error', 'message', 'data', 'code'],
      );
      final val = ExamCoachAnswerUploadReceiptResponse(
        error: $checkedConvert('error', (v) => v as bool),
        message: $checkedConvert('message', (v) => v as String),
        data: $checkedConvert(
          'data',
          (v) =>
              ExamCoachAnswerUploadReceipt.fromJson(v as Map<String, dynamic>),
        ),
        code: $checkedConvert('code', (v) => (v as num).toInt()),
      );
      return val;
    });

Map<String, dynamic> _$ExamCoachAnswerUploadReceiptResponseToJson(
  ExamCoachAnswerUploadReceiptResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
