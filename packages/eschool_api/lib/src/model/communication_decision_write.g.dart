// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'communication_decision_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CommunicationDecisionWrite _$CommunicationDecisionWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('CommunicationDecisionWrite', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const [
      'revision',
      'operation_id',
      'fingerprint',
      'decision',
      'reason',
    ],
  );
  final val = CommunicationDecisionWrite(
    revision: $checkedConvert('revision', (v) => (v as num).toInt()),
    operationId: $checkedConvert('operation_id', (v) => v as String),
    fingerprint: $checkedConvert('fingerprint', (v) => v as String),
    decision: $checkedConvert(
      'decision',
      (v) => $enumDecode(
        _$CommunicationDecisionWriteDecisionEnumEnumMap,
        v,
        unknownValue:
            CommunicationDecisionWriteDecisionEnum.unknownDefaultOpenApi,
      ),
    ),
    reason: $checkedConvert('reason', (v) => v as String),
  );
  return val;
}, fieldKeyMap: const {'operationId': 'operation_id'});

Map<String, dynamic> _$CommunicationDecisionWriteToJson(
  CommunicationDecisionWrite instance,
) => <String, dynamic>{
  'revision': instance.revision,
  'operation_id': instance.operationId,
  'fingerprint': instance.fingerprint,
  'decision':
      _$CommunicationDecisionWriteDecisionEnumEnumMap[instance.decision]!,
  'reason': instance.reason,
};

const _$CommunicationDecisionWriteDecisionEnumEnumMap = {
  CommunicationDecisionWriteDecisionEnum.approve: 'approve',
  CommunicationDecisionWriteDecisionEnum.reject: 'reject',
  CommunicationDecisionWriteDecisionEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
