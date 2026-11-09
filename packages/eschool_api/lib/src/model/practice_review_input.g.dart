// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'practice_review_input.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PracticeReviewInput _$PracticeReviewInputFromJson(Map<String, dynamic> json) =>
    $checkedCreate('PracticeReviewInput', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['operation_id', 'rating']);
      final val = PracticeReviewInput(
        operationId: $checkedConvert('operation_id', (v) => v as String),
        answer: $checkedConvert('answer', (v) => v as String?),
        parts: $checkedConvert(
          'parts',
          (v) => (v as Map<String, dynamic>?)?.map(
            (k, e) => MapEntry(k, e as String),
          ),
        ),
        rating: $checkedConvert('rating', (v) => (v as num).toInt()),
      );
      return val;
    }, fieldKeyMap: const {'operationId': 'operation_id'});

Map<String, dynamic> _$PracticeReviewInputToJson(
  PracticeReviewInput instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'answer': ?instance.answer,
  'parts': ?instance.parts,
  'rating': instance.rating,
};
