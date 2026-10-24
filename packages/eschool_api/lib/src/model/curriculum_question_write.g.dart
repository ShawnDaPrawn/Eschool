// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'curriculum_question_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CurriculumQuestionWrite _$CurriculumQuestionWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('CurriculumQuestionWrite', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['number', 'max_mark', 'topic_keys']);
  final val = CurriculumQuestionWrite(
    number: $checkedConvert('number', (v) => v as String),
    maxMark: $checkedConvert('max_mark', (v) => v as String),
    topicKeys: $checkedConvert(
      'topic_keys',
      (v) => (v as List<dynamic>).map((e) => e as String).toList(),
    ),
  );
  return val;
}, fieldKeyMap: const {'maxMark': 'max_mark', 'topicKeys': 'topic_keys'});

Map<String, dynamic> _$CurriculumQuestionWriteToJson(
  CurriculumQuestionWrite instance,
) => <String, dynamic>{
  'number': instance.number,
  'max_mark': instance.maxMark,
  'topic_keys': instance.topicKeys,
};
