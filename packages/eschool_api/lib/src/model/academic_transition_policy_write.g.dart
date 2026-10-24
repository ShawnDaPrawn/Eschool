// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_transition_policy_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicTransitionPolicyWrite _$AcademicTransitionPolicyWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'AcademicTransitionPolicyWrite',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const ['join_mark_rule', 'change_mark_rule', 'revision'],
    );
    final val = AcademicTransitionPolicyWrite(
      joinMarkRule: $checkedConvert('join_mark_rule', (v) => v as String),
      changeMarkRule: $checkedConvert('change_mark_rule', (v) => v as String),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
    );
    return val;
  },
  fieldKeyMap: const {
    'joinMarkRule': 'join_mark_rule',
    'changeMarkRule': 'change_mark_rule',
  },
);

Map<String, dynamic> _$AcademicTransitionPolicyWriteToJson(
  AcademicTransitionPolicyWrite instance,
) => <String, dynamic>{
  'join_mark_rule': instance.joinMarkRule,
  'change_mark_rule': instance.changeMarkRule,
  'revision': instance.revision,
};
