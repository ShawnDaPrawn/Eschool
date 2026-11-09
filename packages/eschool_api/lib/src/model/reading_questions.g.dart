// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'reading_questions.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ReadingQuestions _$ReadingQuestionsFromJson(Map<String, dynamic> json) =>
    $checkedCreate('ReadingQuestions', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['book_id', 'questions', 'locked']);
      final val = ReadingQuestions(
        bookId: $checkedConvert('book_id', (v) => (v as num).toInt()),
        questions: $checkedConvert(
          'questions',
          (v) => (v as List<dynamic>)
              .map((e) => ReadingQuestion.fromJson(e as Map<String, dynamic>))
              .toList(),
        ),
        locked: $checkedConvert('locked', (v) => (v as num).toInt()),
      );
      return val;
    }, fieldKeyMap: const {'bookId': 'book_id'});

Map<String, dynamic> _$ReadingQuestionsToJson(ReadingQuestions instance) =>
    <String, dynamic>{
      'book_id': instance.bookId,
      'questions': instance.questions.map((e) => e.toJson()).toList(),
      'locked': instance.locked,
    };
