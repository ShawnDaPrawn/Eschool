// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'exam_coach_topic_choice.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ExamCoachTopicChoice _$ExamCoachTopicChoiceFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ExamCoachTopicChoice', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['id', 'name']);
  final val = ExamCoachTopicChoice(
    id: $checkedConvert('id', (v) => (v as num).toInt()),
    name: $checkedConvert('name', (v) => v as String),
  );
  return val;
});

Map<String, dynamic> _$ExamCoachTopicChoiceToJson(
  ExamCoachTopicChoice instance,
) => <String, dynamic>{'id': instance.id, 'name': instance.name};
