// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'communication_rule_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CommunicationRuleWrite _$CommunicationRuleWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'CommunicationRuleWrite',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'operation_id',
        'revision',
        'allow_school',
        'requires_approval',
      ],
    );
    final val = CommunicationRuleWrite(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      allowSchool: $checkedConvert('allow_school', (v) => v as bool),
      requiresApproval: $checkedConvert('requires_approval', (v) => v as bool),
    );
    return val;
  },
  fieldKeyMap: const {
    'operationId': 'operation_id',
    'allowSchool': 'allow_school',
    'requiresApproval': 'requires_approval',
  },
);

Map<String, dynamic> _$CommunicationRuleWriteToJson(
  CommunicationRuleWrite instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'revision': instance.revision,
  'allow_school': instance.allowSchool,
  'requires_approval': instance.requiresApproval,
};
