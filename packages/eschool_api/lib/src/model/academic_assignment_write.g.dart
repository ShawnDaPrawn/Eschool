// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_assignment_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicAssignmentWrite _$AcademicAssignmentWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'AcademicAssignmentWrite',
  json,
  ($checkedConvert) {
    $checkKeys(json, requiredKeys: const ['starts_on', 'ends_on', 'reason']);
    final val = AcademicAssignmentWrite(
      startsOn: $checkedConvert('starts_on', (v) => v as String),
      endsOn: $checkedConvert('ends_on', (v) => v as String),
      reason: $checkedConvert('reason', (v) => v as String),
      revision: $checkedConvert('revision', (v) => (v as num?)?.toInt()),
      learnerId: $checkedConvert('learner_id', (v) => (v as num?)?.toInt()),
      teacherId: $checkedConvert('teacher_id', (v) => (v as num?)?.toInt()),
      classSectionId: $checkedConvert(
        'class_section_id',
        (v) => (v as num?)?.toInt(),
      ),
      sessionYearId: $checkedConvert(
        'session_year_id',
        (v) => (v as num?)?.toInt(),
      ),
      userId: $checkedConvert('user_id', (v) => (v as num?)?.toInt()),
      gradeLevelId: $checkedConvert(
        'grade_level_id',
        (v) => (v as num?)?.toInt(),
      ),
      kind: $checkedConvert(
        'kind',
        (v) => $enumDecodeNullable(
          _$AcademicAssignmentWriteKindEnumEnumMap,
          v,
          unknownValue: AcademicAssignmentWriteKindEnum.unknownDefaultOpenApi,
        ),
      ),
      markRule: $checkedConvert(
        'mark_rule',
        (v) => $enumDecodeNullable(
          _$AcademicAssignmentWriteMarkRuleEnumEnumMap,
          v,
          unknownValue:
              AcademicAssignmentWriteMarkRuleEnum.unknownDefaultOpenApi,
        ),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'startsOn': 'starts_on',
    'endsOn': 'ends_on',
    'learnerId': 'learner_id',
    'teacherId': 'teacher_id',
    'classSectionId': 'class_section_id',
    'sessionYearId': 'session_year_id',
    'userId': 'user_id',
    'gradeLevelId': 'grade_level_id',
    'markRule': 'mark_rule',
  },
);

Map<String, dynamic> _$AcademicAssignmentWriteToJson(
  AcademicAssignmentWrite instance,
) => <String, dynamic>{
  'starts_on': instance.startsOn,
  'ends_on': instance.endsOn,
  'reason': instance.reason,
  'revision': ?instance.revision,
  'learner_id': ?instance.learnerId,
  'teacher_id': ?instance.teacherId,
  'class_section_id': ?instance.classSectionId,
  'session_year_id': ?instance.sessionYearId,
  'user_id': ?instance.userId,
  'grade_level_id': ?instance.gradeLevelId,
  'kind': ?_$AcademicAssignmentWriteKindEnumEnumMap[instance.kind],
  'mark_rule': ?_$AcademicAssignmentWriteMarkRuleEnumEnumMap[instance.markRule],
};

const _$AcademicAssignmentWriteKindEnumEnumMap = {
  AcademicAssignmentWriteKindEnum.gradeHead: 'grade_head',
  AcademicAssignmentWriteKindEnum.principal: 'principal',
  AcademicAssignmentWriteKindEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$AcademicAssignmentWriteMarkRuleEnumEnumMap = {
  AcademicAssignmentWriteMarkRuleEnum.catchUp: 'catch_up',
  AcademicAssignmentWriteMarkRuleEnum.excused: 'excused',
  AcademicAssignmentWriteMarkRuleEnum.carried: 'carried',
  AcademicAssignmentWriteMarkRuleEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
