// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'practice_question_option.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PracticeQuestionOption _$PracticeQuestionOptionFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('PracticeQuestionOption', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['id', 'text']);
  final val = PracticeQuestionOption(
    id: $checkedConvert('id', (v) => v as String),
    text: $checkedConvert('text', (v) => v as String),
  );
  return val;
});

Map<String, dynamic> _$PracticeQuestionOptionToJson(
  PracticeQuestionOption instance,
) => <String, dynamic>{'id': instance.id, 'text': instance.text};
