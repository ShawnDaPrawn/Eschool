// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'consultation_response_answer.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ConsultationResponseAnswer _$ConsultationResponseAnswerFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ConsultationResponseAnswer', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['question_id', 'option_ids', 'text']);
  final val = ConsultationResponseAnswer(
    questionId: $checkedConvert('question_id', (v) => (v as num).toInt()),
    optionIds: $checkedConvert(
      'option_ids',
      (v) => (v as List<dynamic>).map((e) => (e as num).toInt()).toList(),
    ),
    text: $checkedConvert('text', (v) => v as String?),
  );
  return val;
}, fieldKeyMap: const {'questionId': 'question_id', 'optionIds': 'option_ids'});

Map<String, dynamic> _$ConsultationResponseAnswerToJson(
  ConsultationResponseAnswer instance,
) => <String, dynamic>{
  'question_id': instance.questionId,
  'option_ids': instance.optionIds,
  'text': instance.text,
};
