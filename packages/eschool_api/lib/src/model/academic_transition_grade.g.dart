// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_transition_grade.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicTransitionGrade _$AcademicTransitionGradeFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicTransitionGrade', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['id', 'name', 'policy']);
  final val = AcademicTransitionGrade(
    id: $checkedConvert('id', (v) => (v as num).toInt()),
    name: $checkedConvert('name', (v) => v as String),
    policy: $checkedConvert(
      'policy',
      (v) => AcademicTransitionGradePolicy.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$AcademicTransitionGradeToJson(
  AcademicTransitionGrade instance,
) => <String, dynamic>{
  'id': instance.id,
  'name': instance.name,
  'policy': instance.policy.toJson(),
};
