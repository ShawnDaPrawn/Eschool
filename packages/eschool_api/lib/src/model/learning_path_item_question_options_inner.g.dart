// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'learning_path_item_question_options_inner.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

LearningPathItemQuestionOptionsInner
_$LearningPathItemQuestionOptionsInnerFromJson(Map<String, dynamic> json) =>
    $checkedCreate('LearningPathItemQuestionOptionsInner', json, (
      $checkedConvert,
    ) {
      $checkKeys(json, requiredKeys: const ['id', 'text']);
      final val = LearningPathItemQuestionOptionsInner(
        id: $checkedConvert('id', (v) => v as String),
        text: $checkedConvert('text', (v) => v as String),
      );
      return val;
    });

Map<String, dynamic> _$LearningPathItemQuestionOptionsInnerToJson(
  LearningPathItemQuestionOptionsInner instance,
) => <String, dynamic>{'id': instance.id, 'text': instance.text};
