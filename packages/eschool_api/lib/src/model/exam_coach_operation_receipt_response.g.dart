// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'exam_coach_operation_receipt_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ExamCoachOperationReceiptResponse _$ExamCoachOperationReceiptResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ExamCoachOperationReceiptResponse', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = ExamCoachOperationReceiptResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => ExamCoachOperationReceipt.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$ExamCoachOperationReceiptResponseToJson(
  ExamCoachOperationReceiptResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
