// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'exam_coach_answer_list.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ExamCoachAnswerList _$ExamCoachAnswerListFromJson(Map<String, dynamic> json) =>
    $checkedCreate('ExamCoachAnswerList', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['answers', 'page', 'has_more']);
      final val = ExamCoachAnswerList(
        answers: $checkedConvert(
          'answers',
          (v) => (v as List<dynamic>)
              .map(
                (e) =>
                    ExamCoachAnswerSummary.fromJson(e as Map<String, dynamic>),
              )
              .toList(),
        ),
        page: $checkedConvert('page', (v) => (v as num).toInt()),
        hasMore: $checkedConvert('has_more', (v) => v as bool),
      );
      return val;
    }, fieldKeyMap: const {'hasMore': 'has_more'});

Map<String, dynamic> _$ExamCoachAnswerListToJson(
  ExamCoachAnswerList instance,
) => <String, dynamic>{
  'answers': instance.answers.map((e) => e.toJson()).toList(),
  'page': instance.page,
  'has_more': instance.hasMore,
};
