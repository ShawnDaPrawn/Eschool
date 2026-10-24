// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'communication_effective_rules.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CommunicationEffectiveRules _$CommunicationEffectiveRulesFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'CommunicationEffectiveRules',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'allow_school',
        'requires_approval',
        'grade_head_exempt',
        'head_grades',
        'versions',
      ],
    );
    final val = CommunicationEffectiveRules(
      allowSchool: $checkedConvert('allow_school', (v) => v as bool),
      requiresApproval: $checkedConvert('requires_approval', (v) => v as bool),
      gradeHeadExempt: $checkedConvert('grade_head_exempt', (v) => v as bool),
      headGrades: $checkedConvert(
        'head_grades',
        (v) => (v as List<dynamic>).map((e) => (e as num).toInt()).toSet(),
      ),
      versions: $checkedConvert(
        'versions',
        (v) => (v as List<dynamic>)
            .map(
              (e) =>
                  (e as List<dynamic>).map((e) => (e as num).toInt()).toList(),
            )
            .toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'allowSchool': 'allow_school',
    'requiresApproval': 'requires_approval',
    'gradeHeadExempt': 'grade_head_exempt',
    'headGrades': 'head_grades',
  },
);

Map<String, dynamic> _$CommunicationEffectiveRulesToJson(
  CommunicationEffectiveRules instance,
) => <String, dynamic>{
  'allow_school': instance.allowSchool,
  'requires_approval': instance.requiresApproval,
  'grade_head_exempt': instance.gradeHeadExempt,
  'head_grades': instance.headGrades.toList(),
  'versions': instance.versions,
};
