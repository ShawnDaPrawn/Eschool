// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_transition_assignment.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicTransitionAssignment _$AcademicTransitionAssignmentFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicTransitionAssignment', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['id', 'name', 'subject_name']);
  final val = AcademicTransitionAssignment(
    id: $checkedConvert('id', (v) => (v as num).toInt()),
    name: $checkedConvert('name', (v) => v as String),
    subjectName: $checkedConvert('subject_name', (v) => v as String),
  );
  return val;
}, fieldKeyMap: const {'subjectName': 'subject_name'});

Map<String, dynamic> _$AcademicTransitionAssignmentToJson(
  AcademicTransitionAssignment instance,
) => <String, dynamic>{
  'id': instance.id,
  'name': instance.name,
  'subject_name': instance.subjectName,
};
