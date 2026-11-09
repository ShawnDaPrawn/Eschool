// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'learning_path_item_question.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

LearningPathItemQuestion _$LearningPathItemQuestionFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('LearningPathItemQuestion', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['kind', 'prompt', 'options']);
  final val = LearningPathItemQuestion(
    kind: $checkedConvert('kind', (v) => v as String),
    prompt: $checkedConvert('prompt', (v) => v as String),
    options: $checkedConvert(
      'options',
      (v) => (v as List<dynamic>)
          .map(
            (e) => LearningPathItemQuestionOptionsInner.fromJson(
              e as Map<String, dynamic>,
            ),
          )
          .toList(),
    ),
  );
  return val;
});

Map<String, dynamic> _$LearningPathItemQuestionToJson(
  LearningPathItemQuestion instance,
) => <String, dynamic>{
  'kind': instance.kind,
  'prompt': instance.prompt,
  'options': instance.options.map((e) => e.toJson()).toList(),
};
