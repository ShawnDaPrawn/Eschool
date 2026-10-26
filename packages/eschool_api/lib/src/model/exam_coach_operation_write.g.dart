// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'exam_coach_operation_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ExamCoachOperationWrite _$ExamCoachOperationWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ExamCoachOperationWrite', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['operation_id']);
  final val = ExamCoachOperationWrite(
    operationId: $checkedConvert('operation_id', (v) => v as String),
  );
  return val;
}, fieldKeyMap: const {'operationId': 'operation_id'});

Map<String, dynamic> _$ExamCoachOperationWriteToJson(
  ExamCoachOperationWrite instance,
) => <String, dynamic>{'operation_id': instance.operationId};
