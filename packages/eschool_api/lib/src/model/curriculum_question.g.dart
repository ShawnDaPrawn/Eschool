// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'curriculum_question.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CurriculumQuestion _$CurriculumQuestionFromJson(Map<String, dynamic> json) =>
    $checkedCreate('CurriculumQuestion', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['id', 'number', 'max_mark', 'topic_keys'],
      );
      final val = CurriculumQuestion(
        id: $checkedConvert('id', (v) => (v as num).toInt()),
        number: $checkedConvert('number', (v) => v as String),
        maxMark: $checkedConvert('max_mark', (v) => v as String),
        topicKeys: $checkedConvert(
          'topic_keys',
          (v) => (v as List<dynamic>).map((e) => e as String).toList(),
        ),
      );
      return val;
    }, fieldKeyMap: const {'maxMark': 'max_mark', 'topicKeys': 'topic_keys'});

Map<String, dynamic> _$CurriculumQuestionToJson(CurriculumQuestion instance) =>
    <String, dynamic>{
      'id': instance.id,
      'number': instance.number,
      'max_mark': instance.maxMark,
      'topic_keys': instance.topicKeys,
    };
