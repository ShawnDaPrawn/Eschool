// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_assignment.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicAssignment _$AcademicAssignmentFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'AcademicAssignment',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'id',
            'starts_on',
            'ends_on',
            'reason',
            'revision',
            'display_name',
          ],
        );
        final val = AcademicAssignment(
          id: $checkedConvert('id', (v) => (v as num).toInt()),
          startsOn: $checkedConvert('starts_on', (v) => v as String),
          endsOn: $checkedConvert('ends_on', (v) => v as String),
          reason: $checkedConvert('reason', (v) => v as String),
          revision: $checkedConvert('revision', (v) => (v as num).toInt()),
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
              _$AcademicAssignmentKindEnumEnumMap,
              v,
              unknownValue: AcademicAssignmentKindEnum.unknownDefaultOpenApi,
            ),
          ),
          markRule: $checkedConvert(
            'mark_rule',
            (v) => $enumDecodeNullable(
              _$AcademicAssignmentMarkRuleEnumEnumMap,
              v,
              unknownValue:
                  AcademicAssignmentMarkRuleEnum.unknownDefaultOpenApi,
            ),
          ),
          teachingGroupId: $checkedConvert(
            'teaching_group_id',
            (v) => (v as num?)?.toInt(),
          ),
          displayName: $checkedConvert('display_name', (v) => v as String),
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
        'teachingGroupId': 'teaching_group_id',
        'displayName': 'display_name',
      },
    );

Map<String, dynamic> _$AcademicAssignmentToJson(AcademicAssignment instance) =>
    <String, dynamic>{
      'id': instance.id,
      'starts_on': instance.startsOn,
      'ends_on': instance.endsOn,
      'reason': instance.reason,
      'revision': instance.revision,
      'learner_id': ?instance.learnerId,
      'teacher_id': ?instance.teacherId,
      'class_section_id': ?instance.classSectionId,
      'session_year_id': ?instance.sessionYearId,
      'user_id': ?instance.userId,
      'grade_level_id': ?instance.gradeLevelId,
      'kind': ?_$AcademicAssignmentKindEnumEnumMap[instance.kind],
      'mark_rule': ?_$AcademicAssignmentMarkRuleEnumEnumMap[instance.markRule],
      'teaching_group_id': ?instance.teachingGroupId,
      'display_name': instance.displayName,
    };

const _$AcademicAssignmentKindEnumEnumMap = {
  AcademicAssignmentKindEnum.gradeHead: 'grade_head',
  AcademicAssignmentKindEnum.principal: 'principal',
  AcademicAssignmentKindEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};

const _$AcademicAssignmentMarkRuleEnumEnumMap = {
  AcademicAssignmentMarkRuleEnum.catchUp: 'catch_up',
  AcademicAssignmentMarkRuleEnum.excused: 'excused',
  AcademicAssignmentMarkRuleEnum.carried: 'carried',
  AcademicAssignmentMarkRuleEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
