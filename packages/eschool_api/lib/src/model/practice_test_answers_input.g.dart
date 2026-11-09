// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'practice_test_answers_input.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PracticeTestAnswersInput _$PracticeTestAnswersInputFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('PracticeTestAnswersInput', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['answers']);
  final val = PracticeTestAnswersInput(
    answers: $checkedConvert(
      'answers',
      (v) => (v as List<dynamic>)
          .map((e) => PracticeTestAnswer.fromJson(e as Map<String, dynamic>))
          .toList(),
    ),
  );
  return val;
});

Map<String, dynamic> _$PracticeTestAnswersInputToJson(
  PracticeTestAnswersInput instance,
) => <String, dynamic>{
  'answers': instance.answers.map((e) => e.toJson()).toList(),
};
