// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'exam_coach_operation_receipt.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ExamCoachOperationReceipt _$ExamCoachOperationReceiptFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ExamCoachOperationReceipt', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['kind', 'operation_id', 'paper', 'review'],
  );
  final val = ExamCoachOperationReceipt(
    kind: $checkedConvert(
      'kind',
      (v) => $enumDecode(
        _$ExamCoachOperationReceiptKindEnumEnumMap,
        v,
        unknownValue: ExamCoachOperationReceiptKindEnum.unknownDefaultOpenApi,
      ),
    ),
    operationId: $checkedConvert('operation_id', (v) => v as String),
    paper: $checkedConvert(
      'paper',
      (v) => v == null
          ? null
          : ExamCoachPaperReceipt.fromJson(v as Map<String, dynamic>),
    ),
    review: $checkedConvert(
      'review',
      (v) => v == null
          ? null
          : ExamCoachAnswerReviewReceipt.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
}, fieldKeyMap: const {'operationId': 'operation_id'});

Map<String, dynamic> _$ExamCoachOperationReceiptToJson(
  ExamCoachOperationReceipt instance,
) => <String, dynamic>{
  'kind': _$ExamCoachOperationReceiptKindEnumEnumMap[instance.kind]!,
  'operation_id': instance.operationId,
  'paper': instance.paper?.toJson(),
  'review': instance.review?.toJson(),
};

const _$ExamCoachOperationReceiptKindEnumEnumMap = {
  ExamCoachOperationReceiptKindEnum.paper: 'paper',
  ExamCoachOperationReceiptKindEnum.answerReview: 'answer_review',
  ExamCoachOperationReceiptKindEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
