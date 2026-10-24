// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_transition_input.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicTransitionInput _$AcademicTransitionInputFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'AcademicTransitionInput',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'kind',
        'learner_id',
        'session_year_id',
        'effective_on',
        'reason',
      ],
    );
    final val = AcademicTransitionInput(
      kind: $checkedConvert('kind', (v) => v as String),
      learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
      sessionYearId: $checkedConvert(
        'session_year_id',
        (v) => (v as num).toInt(),
      ),
      effectiveOn: $checkedConvert('effective_on', (v) => v as String),
      reason: $checkedConvert('reason', (v) => v as String),
      classSectionId: $checkedConvert(
        'class_section_id',
        (v) => (v as num?)?.toInt(),
      ),
      groupIds: $checkedConvert(
        'group_ids',
        (v) => (v as List<dynamic>?)?.map((e) => (e as num).toInt()).toSet(),
      ),
      feeProrataRequested: $checkedConvert(
        'fee_prorata_requested',
        (v) => v as bool?,
      ),
      sourceMembershipId: $checkedConvert(
        'source_membership_id',
        (v) => (v as num?)?.toInt(),
      ),
      targetGroupId: $checkedConvert(
        'target_group_id',
        (v) => (v as num?)?.toInt(),
      ),
      markRuleOverride: $checkedConvert(
        'mark_rule_override',
        (v) => v as String?,
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'learnerId': 'learner_id',
    'sessionYearId': 'session_year_id',
    'effectiveOn': 'effective_on',
    'classSectionId': 'class_section_id',
    'groupIds': 'group_ids',
    'feeProrataRequested': 'fee_prorata_requested',
    'sourceMembershipId': 'source_membership_id',
    'targetGroupId': 'target_group_id',
    'markRuleOverride': 'mark_rule_override',
  },
);

Map<String, dynamic> _$AcademicTransitionInputToJson(
  AcademicTransitionInput instance,
) => <String, dynamic>{
  'kind': instance.kind,
  'learner_id': instance.learnerId,
  'session_year_id': instance.sessionYearId,
  'effective_on': instance.effectiveOn,
  'reason': instance.reason,
  'class_section_id': ?instance.classSectionId,
  'group_ids': ?instance.groupIds?.toList(),
  'fee_prorata_requested': ?instance.feeProrataRequested,
  'source_membership_id': ?instance.sourceMembershipId,
  'target_group_id': ?instance.targetGroupId,
  'mark_rule_override': ?instance.markRuleOverride,
};
