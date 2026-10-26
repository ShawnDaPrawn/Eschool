// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'exam_coach_question.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ExamCoachQuestion _$ExamCoachQuestionFromJson(Map<String, dynamic> json) =>
    $checkedCreate('ExamCoachQuestion', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['number', 'prompt', 'max_units', 'topic_ids'],
      );
      final val = ExamCoachQuestion(
        number: $checkedConvert('number', (v) => v as String),
        prompt: $checkedConvert('prompt', (v) => v as String),
        memo: $checkedConvert('memo', (v) => v as String?),
        maxUnits: $checkedConvert('max_units', (v) => (v as num).toInt()),
        topicIds: $checkedConvert(
          'topic_ids',
          (v) => (v as List<dynamic>).map((e) => (e as num).toInt()).toList(),
        ),
      );
      return val;
    }, fieldKeyMap: const {'maxUnits': 'max_units', 'topicIds': 'topic_ids'});

Map<String, dynamic> _$ExamCoachQuestionToJson(ExamCoachQuestion instance) =>
    <String, dynamic>{
      'number': instance.number,
      'prompt': instance.prompt,
      'memo': ?instance.memo,
      'max_units': instance.maxUnits,
      'topic_ids': instance.topicIds,
    };
