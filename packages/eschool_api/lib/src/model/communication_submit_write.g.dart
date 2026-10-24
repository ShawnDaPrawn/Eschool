// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'communication_submit_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CommunicationSubmitWrite _$CommunicationSubmitWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('CommunicationSubmitWrite', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['revision', 'operation_id', 'fingerprint'],
  );
  final val = CommunicationSubmitWrite(
    revision: $checkedConvert('revision', (v) => (v as num).toInt()),
    operationId: $checkedConvert('operation_id', (v) => v as String),
    fingerprint: $checkedConvert('fingerprint', (v) => v as String),
  );
  return val;
}, fieldKeyMap: const {'operationId': 'operation_id'});

Map<String, dynamic> _$CommunicationSubmitWriteToJson(
  CommunicationSubmitWrite instance,
) => <String, dynamic>{
  'revision': instance.revision,
  'operation_id': instance.operationId,
  'fingerprint': instance.fingerprint,
};
