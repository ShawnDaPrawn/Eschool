// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'foundation_operation_input.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FoundationOperationInput _$FoundationOperationInputFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('FoundationOperationInput', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['operation_id']);
  final val = FoundationOperationInput(
    operationId: $checkedConvert('operation_id', (v) => v as String),
  );
  return val;
}, fieldKeyMap: const {'operationId': 'operation_id'});

Map<String, dynamic> _$FoundationOperationInputToJson(
  FoundationOperationInput instance,
) => <String, dynamic>{'operation_id': instance.operationId};
