// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'teaching_operation_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TeachingOperationWrite _$TeachingOperationWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('TeachingOperationWrite', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['operation_id']);
  final val = TeachingOperationWrite(
    operationId: $checkedConvert('operation_id', (v) => v as String),
  );
  return val;
}, fieldKeyMap: const {'operationId': 'operation_id'});

Map<String, dynamic> _$TeachingOperationWriteToJson(
  TeachingOperationWrite instance,
) => <String, dynamic>{'operation_id': instance.operationId};
