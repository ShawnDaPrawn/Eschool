// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_transition_learner_choice.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicTransitionLearnerChoice _$AcademicTransitionLearnerChoiceFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicTransitionLearnerChoice', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['id', 'name', 'student_number']);
  final val = AcademicTransitionLearnerChoice(
    id: $checkedConvert('id', (v) => (v as num).toInt()),
    name: $checkedConvert('name', (v) => v as String),
    studentNumber: $checkedConvert('student_number', (v) => v as String?),
  );
  return val;
}, fieldKeyMap: const {'studentNumber': 'student_number'});

Map<String, dynamic> _$AcademicTransitionLearnerChoiceToJson(
  AcademicTransitionLearnerChoice instance,
) => <String, dynamic>{
  'id': instance.id,
  'name': instance.name,
  'student_number': instance.studentNumber,
};
