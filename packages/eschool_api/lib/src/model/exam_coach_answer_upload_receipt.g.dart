// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'exam_coach_answer_upload_receipt.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ExamCoachAnswerUploadReceipt _$ExamCoachAnswerUploadReceiptFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ExamCoachAnswerUploadReceipt', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['id', 'operation_id', 'state']);
  final val = ExamCoachAnswerUploadReceipt(
    id: $checkedConvert('id', (v) => v as String),
    operationId: $checkedConvert('operation_id', (v) => v as String),
    state: $checkedConvert(
      'state',
      (v) => $enumDecode(
        _$ExamCoachAnswerUploadReceiptStateEnumEnumMap,
        v,
        unknownValue:
            ExamCoachAnswerUploadReceiptStateEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
}, fieldKeyMap: const {'operationId': 'operation_id'});

Map<String, dynamic> _$ExamCoachAnswerUploadReceiptToJson(
  ExamCoachAnswerUploadReceipt instance,
) => <String, dynamic>{
  'id': instance.id,
  'operation_id': instance.operationId,
  'state': _$ExamCoachAnswerUploadReceiptStateEnumEnumMap[instance.state]!,
};

const _$ExamCoachAnswerUploadReceiptStateEnumEnumMap = {
  ExamCoachAnswerUploadReceiptStateEnum.prepared: 'prepared',
  ExamCoachAnswerUploadReceiptStateEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
