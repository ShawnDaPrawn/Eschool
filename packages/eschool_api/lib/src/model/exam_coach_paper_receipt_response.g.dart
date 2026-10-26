// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'exam_coach_paper_receipt_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ExamCoachPaperReceiptResponse _$ExamCoachPaperReceiptResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ExamCoachPaperReceiptResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = ExamCoachPaperReceiptResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => ExamCoachPaperReceipt.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$ExamCoachPaperReceiptResponseToJson(
  ExamCoachPaperReceiptResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
