// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'school_assistance_answer.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SchoolAssistanceAnswer _$SchoolAssistanceAnswerFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SchoolAssistanceAnswer', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['language', 'text', 'citations', 'handoff'],
  );
  final val = SchoolAssistanceAnswer(
    language: $checkedConvert('language', (v) => v as String),
    text: $checkedConvert('text', (v) => v as String),
    citations: $checkedConvert(
      'citations',
      (v) => (v as List<dynamic>).map((e) => e as String).toList(),
    ),
    handoff: $checkedConvert('handoff', (v) => v as bool),
  );
  return val;
});

Map<String, dynamic> _$SchoolAssistanceAnswerToJson(
  SchoolAssistanceAnswer instance,
) => <String, dynamic>{
  'language': instance.language,
  'text': instance.text,
  'citations': instance.citations,
  'handoff': instance.handoff,
};
