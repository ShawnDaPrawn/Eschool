// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'communication_role_rule.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CommunicationRoleRule _$CommunicationRoleRuleFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'CommunicationRoleRule',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'role_id',
        'role',
        'allow_school',
        'requires_approval',
        'revision',
      ],
    );
    final val = CommunicationRoleRule(
      roleId: $checkedConvert('role_id', (v) => (v as num).toInt()),
      role: $checkedConvert('role', (v) => v as String),
      allowSchool: $checkedConvert('allow_school', (v) => v as bool),
      requiresApproval: $checkedConvert('requires_approval', (v) => v as bool),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
    );
    return val;
  },
  fieldKeyMap: const {
    'roleId': 'role_id',
    'allowSchool': 'allow_school',
    'requiresApproval': 'requires_approval',
  },
);

Map<String, dynamic> _$CommunicationRoleRuleToJson(
  CommunicationRoleRule instance,
) => <String, dynamic>{
  'role_id': instance.roleId,
  'role': instance.role,
  'allow_school': instance.allowSchool,
  'requires_approval': instance.requiresApproval,
  'revision': instance.revision,
};
