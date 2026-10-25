// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'communication_withdraw_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CommunicationWithdrawWrite _$CommunicationWithdrawWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('CommunicationWithdrawWrite', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['operation_id', 'revision', 'reason']);
  final val = CommunicationWithdrawWrite(
    operationId: $checkedConvert('operation_id', (v) => v as String),
    revision: $checkedConvert('revision', (v) => (v as num).toInt()),
    reason: $checkedConvert('reason', (v) => v as String),
  );
  return val;
}, fieldKeyMap: const {'operationId': 'operation_id'});

Map<String, dynamic> _$CommunicationWithdrawWriteToJson(
  CommunicationWithdrawWrite instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'revision': instance.revision,
  'reason': instance.reason,
};
