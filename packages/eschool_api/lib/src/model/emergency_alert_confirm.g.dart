// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'emergency_alert_confirm.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

EmergencyAlertConfirm _$EmergencyAlertConfirmFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('EmergencyAlertConfirm', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['operation_id', 'revision', 'confirmation'],
  );
  final val = EmergencyAlertConfirm(
    operationId: $checkedConvert('operation_id', (v) => v as String),
    revision: $checkedConvert('revision', (v) => (v as num).toInt()),
    confirmation: $checkedConvert('confirmation', (v) => v as String),
  );
  return val;
}, fieldKeyMap: const {'operationId': 'operation_id'});

Map<String, dynamic> _$EmergencyAlertConfirmToJson(
  EmergencyAlertConfirm instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'revision': instance.revision,
  'confirmation': instance.confirmation,
};
