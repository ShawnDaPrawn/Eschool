// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'communication_smart_rules_inner.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CommunicationSmartRulesInner _$CommunicationSmartRulesInnerFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('CommunicationSmartRulesInner', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['kind', 'target_id']);
  final val = CommunicationSmartRulesInner(
    kind: $checkedConvert(
      'kind',
      (v) => $enumDecode(
        _$CommunicationSmartRulesInnerKindEnumEnumMap,
        v,
        unknownValue:
            CommunicationSmartRulesInnerKindEnum.unknownDefaultOpenApi,
      ),
    ),
    targetId: $checkedConvert('target_id', (v) => (v as num).toInt()),
  );
  return val;
}, fieldKeyMap: const {'targetId': 'target_id'});

Map<String, dynamic> _$CommunicationSmartRulesInnerToJson(
  CommunicationSmartRulesInner instance,
) => <String, dynamic>{
  'kind': _$CommunicationSmartRulesInnerKindEnumEnumMap[instance.kind]!,
  'target_id': instance.targetId,
};

const _$CommunicationSmartRulesInnerKindEnumEnumMap = {
  CommunicationSmartRulesInnerKindEnum.grade: 'grade',
  CommunicationSmartRulesInnerKindEnum.homeroom: 'homeroom',
  CommunicationSmartRulesInnerKindEnum.group: 'group',
  CommunicationSmartRulesInnerKindEnum.activityGroup: 'activity_group',
  CommunicationSmartRulesInnerKindEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
