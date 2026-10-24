// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'communication_rule_list.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CommunicationRuleList _$CommunicationRuleListFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('CommunicationRuleList', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['items']);
  final val = CommunicationRuleList(
    items: $checkedConvert(
      'items',
      (v) => (v as List<dynamic>)
          .map((e) => CommunicationRoleRule.fromJson(e as Map<String, dynamic>))
          .toList(),
    ),
  );
  return val;
});

Map<String, dynamic> _$CommunicationRuleListToJson(
  CommunicationRuleList instance,
) => <String, dynamic>{'items': instance.items.map((e) => e.toJson()).toList()};
