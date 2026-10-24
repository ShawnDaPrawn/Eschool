// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'communication_role_rule_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CommunicationRoleRuleResponse _$CommunicationRoleRuleResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('CommunicationRoleRuleResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = CommunicationRoleRuleResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => CommunicationRoleRule.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$CommunicationRoleRuleResponseToJson(
  CommunicationRoleRuleResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
