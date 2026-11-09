// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'pool_cancel_input.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PoolCancelInput _$PoolCancelInputFromJson(Map<String, dynamic> json) =>
    $checkedCreate('PoolCancelInput', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['operation_id', 'revision']);
      final val = PoolCancelInput(
        operationId: $checkedConvert('operation_id', (v) => v as String),
        revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      );
      return val;
    }, fieldKeyMap: const {'operationId': 'operation_id'});

Map<String, dynamic> _$PoolCancelInputToJson(PoolCancelInput instance) =>
    <String, dynamic>{
      'operation_id': instance.operationId,
      'revision': instance.revision,
    };
