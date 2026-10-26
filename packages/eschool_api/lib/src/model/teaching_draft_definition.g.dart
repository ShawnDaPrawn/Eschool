// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'teaching_draft_definition.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TeachingDraftDefinition _$TeachingDraftDefinitionFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('TeachingDraftDefinition', json, ($checkedConvert) {
  final val = TeachingDraftDefinition(
    title: $checkedConvert('title', (v) => v as String?),
    sections: $checkedConvert(
      'sections',
      (v) => (v as List<dynamic>?)
          ?.map((e) => TeachingDraftSection.fromJson(e as Map<String, dynamic>))
          .toList(),
    ),
    text: $checkedConvert('text', (v) => v as String?),
    questions: $checkedConvert(
      'questions',
      (v) => (v as List<dynamic>?)
          ?.map(
            (e) => TeachingPracticeQuestion.fromJson(e as Map<String, dynamic>),
          )
          .toList(),
    ),
    questionMarks: $checkedConvert(
      'question_marks',
      (v) => (v as List<dynamic>?)
          ?.map(
            (e) => TeachingScriptProposal.fromJson(e as Map<String, dynamic>),
          )
          .toList(),
    ),
  );
  return val;
}, fieldKeyMap: const {'questionMarks': 'question_marks'});

Map<String, dynamic> _$TeachingDraftDefinitionToJson(
  TeachingDraftDefinition instance,
) => <String, dynamic>{
  'title': ?instance.title,
  'sections': ?instance.sections?.map((e) => e.toJson()).toList(),
  'text': ?instance.text,
  'questions': ?instance.questions?.map((e) => e.toJson()).toList(),
  'question_marks': ?instance.questionMarks?.map((e) => e.toJson()).toList(),
};
