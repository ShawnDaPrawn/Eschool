// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'subscription_operation_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SubscriptionOperationWrite _$SubscriptionOperationWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SubscriptionOperationWrite', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['operation_id']);
  final val = SubscriptionOperationWrite(
    operationId: $checkedConvert('operation_id', (v) => v as String),
  );
  return val;
}, fieldKeyMap: const {'operationId': 'operation_id'});

Map<String, dynamic> _$SubscriptionOperationWriteToJson(
  SubscriptionOperationWrite instance,
) => <String, dynamic>{'operation_id': instance.operationId};
