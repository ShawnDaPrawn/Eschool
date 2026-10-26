// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'teaching_question_label.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TeachingQuestionLabel _$TeachingQuestionLabelFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('TeachingQuestionLabel', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['question_id', 'number', 'max_mark']);
  final val = TeachingQuestionLabel(
    questionId: $checkedConvert('question_id', (v) => (v as num).toInt()),
    number: $checkedConvert('number', (v) => v as String),
    maxMark: $checkedConvert('max_mark', (v) => v as String),
  );
  return val;
}, fieldKeyMap: const {'questionId': 'question_id', 'maxMark': 'max_mark'});

Map<String, dynamic> _$TeachingQuestionLabelToJson(
  TeachingQuestionLabel instance,
) => <String, dynamic>{
  'question_id': instance.questionId,
  'number': instance.number,
  'max_mark': instance.maxMark,
};
