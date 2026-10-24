// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_transition_grade_policy.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicTransitionGradePolicy _$AcademicTransitionGradePolicyFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'AcademicTransitionGradePolicy',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'grade_level_id',
        'join_mark_rule',
        'change_mark_rule',
        'revision',
      ],
    );
    final val = AcademicTransitionGradePolicy(
      gradeLevelId: $checkedConvert(
        'grade_level_id',
        (v) => (v as num).toInt(),
      ),
      joinMarkRule: $checkedConvert('join_mark_rule', (v) => v as String),
      changeMarkRule: $checkedConvert('change_mark_rule', (v) => v as String),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
    );
    return val;
  },
  fieldKeyMap: const {
    'gradeLevelId': 'grade_level_id',
    'joinMarkRule': 'join_mark_rule',
    'changeMarkRule': 'change_mark_rule',
  },
);

Map<String, dynamic> _$AcademicTransitionGradePolicyToJson(
  AcademicTransitionGradePolicy instance,
) => <String, dynamic>{
  'grade_level_id': instance.gradeLevelId,
  'join_mark_rule': instance.joinMarkRule,
  'change_mark_rule': instance.changeMarkRule,
  'revision': instance.revision,
};
