// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'exam_coach_question_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ExamCoachQuestionWrite _$ExamCoachQuestionWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ExamCoachQuestionWrite', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['number', 'prompt', 'memo', 'max_units', 'topic_ids'],
  );
  final val = ExamCoachQuestionWrite(
    number: $checkedConvert('number', (v) => v as String),
    prompt: $checkedConvert('prompt', (v) => v as String),
    memo: $checkedConvert('memo', (v) => v as String),
    maxUnits: $checkedConvert('max_units', (v) => (v as num).toInt()),
    topicIds: $checkedConvert(
      'topic_ids',
      (v) => (v as List<dynamic>).map((e) => (e as num).toInt()).toList(),
    ),
  );
  return val;
}, fieldKeyMap: const {'maxUnits': 'max_units', 'topicIds': 'topic_ids'});

Map<String, dynamic> _$ExamCoachQuestionWriteToJson(
  ExamCoachQuestionWrite instance,
) => <String, dynamic>{
  'number': instance.number,
  'prompt': instance.prompt,
  'memo': instance.memo,
  'max_units': instance.maxUnits,
  'topic_ids': instance.topicIds,
};
