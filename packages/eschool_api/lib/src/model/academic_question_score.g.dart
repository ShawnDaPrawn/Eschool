// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_question_score.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicQuestionScore _$AcademicQuestionScoreFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicQuestionScore', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['question_id', 'mark']);
  final val = AcademicQuestionScore(
    questionId: $checkedConvert('question_id', (v) => (v as num).toInt()),
    mark: $checkedConvert('mark', (v) => v as String?),
  );
  return val;
}, fieldKeyMap: const {'questionId': 'question_id'});

Map<String, dynamic> _$AcademicQuestionScoreToJson(
  AcademicQuestionScore instance,
) => <String, dynamic>{
  'question_id': instance.questionId,
  'mark': instance.mark,
};
