// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'tutor_operation_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TutorOperationWrite _$TutorOperationWriteFromJson(Map<String, dynamic> json) =>
    $checkedCreate('TutorOperationWrite', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['operation_id']);
      final val = TutorOperationWrite(
        operationId: $checkedConvert('operation_id', (v) => v as String),
      );
      return val;
    }, fieldKeyMap: const {'operationId': 'operation_id'});

Map<String, dynamic> _$TutorOperationWriteToJson(
  TutorOperationWrite instance,
) => <String, dynamic>{'operation_id': instance.operationId};
