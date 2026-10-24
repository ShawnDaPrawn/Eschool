// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_transition_membership.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicTransitionMembership _$AcademicTransitionMembershipFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'AcademicTransitionMembership',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'teaching_group_id',
        'session_year_id',
        'grade_level_id',
        'revision',
        'starts_on',
        'ends_on',
        'mark_rule',
        'cancelled_at',
      ],
    );
    final val = AcademicTransitionMembership(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      teachingGroupId: $checkedConvert(
        'teaching_group_id',
        (v) => (v as num).toInt(),
      ),
      sessionYearId: $checkedConvert(
        'session_year_id',
        (v) => (v as num?)?.toInt(),
      ),
      gradeLevelId: $checkedConvert(
        'grade_level_id',
        (v) => (v as num?)?.toInt(),
      ),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      startsOn: $checkedConvert('starts_on', (v) => v as String),
      endsOn: $checkedConvert('ends_on', (v) => v as String),
      markRule: $checkedConvert('mark_rule', (v) => v as String),
      cancelledAt: $checkedConvert(
        'cancelled_at',
        (v) => v == null ? null : DateTime.parse(v as String),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'teachingGroupId': 'teaching_group_id',
    'sessionYearId': 'session_year_id',
    'gradeLevelId': 'grade_level_id',
    'startsOn': 'starts_on',
    'endsOn': 'ends_on',
    'markRule': 'mark_rule',
    'cancelledAt': 'cancelled_at',
  },
);

Map<String, dynamic> _$AcademicTransitionMembershipToJson(
  AcademicTransitionMembership instance,
) => <String, dynamic>{
  'id': instance.id,
  'teaching_group_id': instance.teachingGroupId,
  'session_year_id': instance.sessionYearId,
  'grade_level_id': instance.gradeLevelId,
  'revision': instance.revision,
  'starts_on': instance.startsOn,
  'ends_on': instance.endsOn,
  'mark_rule': instance.markRule,
  'cancelled_at': instance.cancelledAt?.toIso8601String(),
};
