// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'tutor_feedback.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TutorFeedback _$TutorFeedbackFromJson(Map<String, dynamic> json) =>
    $checkedCreate('TutorFeedback', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['subject_token', 'kind', 'text', 'question'],
      );
      final val = TutorFeedback(
        subjectToken: $checkedConvert('subject_token', (v) => v as String),
        kind: $checkedConvert(
          'kind',
          (v) => $enumDecode(
            _$TutorFeedbackKindEnumEnumMap,
            v,
            unknownValue: TutorFeedbackKindEnum.unknownDefaultOpenApi,
          ),
        ),
        text: $checkedConvert('text', (v) => v as String),
        question: $checkedConvert('question', (v) => v as String),
      );
      return val;
    }, fieldKeyMap: const {'subjectToken': 'subject_token'});

Map<String, dynamic> _$TutorFeedbackToJson(TutorFeedback instance) =>
    <String, dynamic>{
      'subject_token': instance.subjectToken,
      'kind': _$TutorFeedbackKindEnumEnumMap[instance.kind]!,
      'text': instance.text,
      'question': instance.question,
    };

const _$TutorFeedbackKindEnumEnumMap = {
  TutorFeedbackKindEnum.hint: 'hint',
  TutorFeedbackKindEnum.question: 'question',
  TutorFeedbackKindEnum.correction: 'correction',
  TutorFeedbackKindEnum.refusal: 'refusal',
  TutorFeedbackKindEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
