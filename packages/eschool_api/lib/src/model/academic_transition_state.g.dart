// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_transition_state.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicTransitionState _$AcademicTransitionStateFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicTransitionState', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['enrolments', 'memberships', 'changes'],
  );
  final val = AcademicTransitionState(
    enrolments: $checkedConvert(
      'enrolments',
      (v) => (v as List<dynamic>)
          .map(
            (e) =>
                AcademicTransitionEnrolment.fromJson(e as Map<String, dynamic>),
          )
          .toList(),
    ),
    memberships: $checkedConvert(
      'memberships',
      (v) => (v as List<dynamic>)
          .map(
            (e) => AcademicTransitionMembership.fromJson(
              e as Map<String, dynamic>,
            ),
          )
          .toList(),
    ),
    changes: $checkedConvert(
      'changes',
      (v) => (v as List<dynamic>)
          .map(
            (e) => AcademicTransitionFact.fromJson(e as Map<String, dynamic>),
          )
          .toList(),
    ),
  );
  return val;
});

Map<String, dynamic> _$AcademicTransitionStateToJson(
  AcademicTransitionState instance,
) => <String, dynamic>{
  'enrolments': instance.enrolments.map((e) => e.toJson()).toList(),
  'memberships': instance.memberships.map((e) => e.toJson()).toList(),
  'changes': instance.changes.map((e) => e.toJson()).toList(),
};
