// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'communication_withdraw_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CommunicationWithdrawWrite _$CommunicationWithdrawWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('CommunicationWithdrawWrite', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['revision', 'operation_id', 'reason']);
  final val = CommunicationWithdrawWrite(
    revision: $checkedConvert('revision', (v) => (v as num).toInt()),
    operationId: $checkedConvert('operation_id', (v) => v as String),
    reason: $checkedConvert('reason', (v) => v as String),
  );
  return val;
}, fieldKeyMap: const {'operationId': 'operation_id'});

Map<String, dynamic> _$CommunicationWithdrawWriteToJson(
  CommunicationWithdrawWrite instance,
) => <String, dynamic>{
  'revision': instance.revision,
  'operation_id': instance.operationId,
  'reason': instance.reason,
};
