// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'practice_test_start_input.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PracticeTestStartInput _$PracticeTestStartInputFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('PracticeTestStartInput', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['operation_id']);
  final val = PracticeTestStartInput(
    operationId: $checkedConvert('operation_id', (v) => v as String),
  );
  return val;
}, fieldKeyMap: const {'operationId': 'operation_id'});

Map<String, dynamic> _$PracticeTestStartInputToJson(
  PracticeTestStartInput instance,
) => <String, dynamic>{'operation_id': instance.operationId};
