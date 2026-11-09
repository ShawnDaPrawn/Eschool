// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'reading_question.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ReadingQuestion _$ReadingQuestionFromJson(Map<String, dynamic> json) =>
    $checkedCreate('ReadingQuestion', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['id', 'after_page', 'prompt', 'options', 'result'],
      );
      final val = ReadingQuestion(
        id: $checkedConvert('id', (v) => (v as num).toInt()),
        afterPage: $checkedConvert('after_page', (v) => (v as num?)?.toInt()),
        prompt: $checkedConvert('prompt', (v) => v as String),
        options: $checkedConvert(
          'options',
          (v) => (v as List<dynamic>).map((e) => e as String).toList(),
        ),
        result: $checkedConvert(
          'result',
          (v) => v == null
              ? null
              : ReadingResult.fromJson(v as Map<String, dynamic>),
        ),
      );
      return val;
    }, fieldKeyMap: const {'afterPage': 'after_page'});

Map<String, dynamic> _$ReadingQuestionToJson(ReadingQuestion instance) =>
    <String, dynamic>{
      'id': instance.id,
      'after_page': instance.afterPage,
      'prompt': instance.prompt,
      'options': instance.options,
      'result': instance.result?.toJson(),
    };
