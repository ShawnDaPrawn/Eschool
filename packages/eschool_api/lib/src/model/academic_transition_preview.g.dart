// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_transition_preview.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicTransitionPreview _$AcademicTransitionPreviewFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'AcademicTransitionPreview',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'input',
        'fingerprint',
        'learner_name',
        'grade_level_id',
        'homeroom_name',
        'policy',
        'mark_rule',
        'membership_ends_on',
        'group_assignments',
        'state',
      ],
    );
    final val = AcademicTransitionPreview(
      input: $checkedConvert(
        'input',
        (v) => AcademicTransitionInput.fromJson(v as Map<String, dynamic>),
      ),
      fingerprint: $checkedConvert('fingerprint', (v) => v as String),
      learnerName: $checkedConvert('learner_name', (v) => v as String),
      gradeLevelId: $checkedConvert(
        'grade_level_id',
        (v) => (v as num?)?.toInt(),
      ),
      homeroomName: $checkedConvert('homeroom_name', (v) => v as String),
      policy: $checkedConvert(
        'policy',
        (v) => v == null
            ? null
            : AcademicTransitionGradePolicy.fromJson(v as Map<String, dynamic>),
      ),
      markRule: $checkedConvert('mark_rule', (v) => v as String?),
      membershipEndsOn: $checkedConvert(
        'membership_ends_on',
        (v) => v as String,
      ),
      groupAssignments: $checkedConvert(
        'group_assignments',
        (v) => (v as List<dynamic>)
            .map(
              (e) => AcademicTransitionAssignment.fromJson(
                e as Map<String, dynamic>,
              ),
            )
            .toList(),
      ),
      state: $checkedConvert(
        'state',
        (v) => AcademicTransitionState.fromJson(v as Map<String, dynamic>),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'learnerName': 'learner_name',
    'gradeLevelId': 'grade_level_id',
    'homeroomName': 'homeroom_name',
    'markRule': 'mark_rule',
    'membershipEndsOn': 'membership_ends_on',
    'groupAssignments': 'group_assignments',
  },
);

Map<String, dynamic> _$AcademicTransitionPreviewToJson(
  AcademicTransitionPreview instance,
) => <String, dynamic>{
  'input': instance.input.toJson(),
  'fingerprint': instance.fingerprint,
  'learner_name': instance.learnerName,
  'grade_level_id': instance.gradeLevelId,
  'homeroom_name': instance.homeroomName,
  'policy': instance.policy?.toJson(),
  'mark_rule': instance.markRule,
  'membership_ends_on': instance.membershipEndsOn,
  'group_assignments': instance.groupAssignments
      .map((e) => e.toJson())
      .toList(),
  'state': instance.state.toJson(),
};
