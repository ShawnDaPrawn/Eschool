// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'exam_coach_group_choice.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ExamCoachGroupChoice _$ExamCoachGroupChoiceFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ExamCoachGroupChoice', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['id', 'name', 'subject_name']);
  final val = ExamCoachGroupChoice(
    id: $checkedConvert('id', (v) => (v as num).toInt()),
    name: $checkedConvert('name', (v) => v as String),
    subjectName: $checkedConvert('subject_name', (v) => v as String),
  );
  return val;
}, fieldKeyMap: const {'subjectName': 'subject_name'});

Map<String, dynamic> _$ExamCoachGroupChoiceToJson(
  ExamCoachGroupChoice instance,
) => <String, dynamic>{
  'id': instance.id,
  'name': instance.name,
  'subject_name': instance.subjectName,
};
