// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'shared_library_item_question.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SharedLibraryItemQuestion _$SharedLibraryItemQuestionFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SharedLibraryItemQuestion', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const [
      'kind',
      'prompt',
      'options',
      'answers',
      'steps',
      'explanation',
      'max_seconds',
    ],
  );
  final val = SharedLibraryItemQuestion(
    kind: $checkedConvert('kind', (v) => v as String),
    prompt: $checkedConvert('prompt', (v) => v as String),
    options: $checkedConvert(
      'options',
      (v) => (v as List<dynamic>).map((e) => e as Object).toList(),
    ),
    answers: $checkedConvert(
      'answers',
      (v) => (v as List<dynamic>).map((e) => e as String).toList(),
    ),
    steps: $checkedConvert(
      'steps',
      (v) => (v as List<dynamic>).map((e) => e as Object).toList(),
    ),
    explanation: $checkedConvert('explanation', (v) => v as String),
    maxSeconds: $checkedConvert('max_seconds', (v) => (v as num?)?.toInt()),
  );
  return val;
}, fieldKeyMap: const {'maxSeconds': 'max_seconds'});

Map<String, dynamic> _$SharedLibraryItemQuestionToJson(
  SharedLibraryItemQuestion instance,
) => <String, dynamic>{
  'kind': instance.kind,
  'prompt': instance.prompt,
  'options': instance.options,
  'answers': instance.answers,
  'steps': instance.steps,
  'explanation': instance.explanation,
  'max_seconds': instance.maxSeconds,
};
