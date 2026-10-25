// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'transfer_revision_input.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TransferRevisionInput _$TransferRevisionInputFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('TransferRevisionInput', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['operation_id', 'revision']);
  final val = TransferRevisionInput(
    operationId: $checkedConvert('operation_id', (v) => v as String),
    revision: $checkedConvert('revision', (v) => (v as num).toInt()),
  );
  return val;
}, fieldKeyMap: const {'operationId': 'operation_id'});

Map<String, dynamic> _$TransferRevisionInputToJson(
  TransferRevisionInput instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'revision': instance.revision,
};
