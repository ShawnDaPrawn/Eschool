// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'exam_coach_withdrawal_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ExamCoachWithdrawalWrite _$ExamCoachWithdrawalWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'ExamCoachWithdrawalWrite',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const ['operation_id', 'expected_revision', 'reason'],
    );
    final val = ExamCoachWithdrawalWrite(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      expectedRevision: $checkedConvert(
        'expected_revision',
        (v) => (v as num).toInt(),
      ),
      reason: $checkedConvert('reason', (v) => v as String),
    );
    return val;
  },
  fieldKeyMap: const {
    'operationId': 'operation_id',
    'expectedRevision': 'expected_revision',
  },
);

Map<String, dynamic> _$ExamCoachWithdrawalWriteToJson(
  ExamCoachWithdrawalWrite instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'expected_revision': instance.expectedRevision,
  'reason': instance.reason,
};
