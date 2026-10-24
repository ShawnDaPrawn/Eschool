// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'communication_rule_list_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CommunicationRuleListResponse _$CommunicationRuleListResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('CommunicationRuleListResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = CommunicationRuleListResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => CommunicationRuleList.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$CommunicationRuleListResponseToJson(
  CommunicationRuleListResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
