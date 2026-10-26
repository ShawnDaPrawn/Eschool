// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'foundation_review_input.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FoundationReviewInput _$FoundationReviewInputFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('FoundationReviewInput', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['operation_id', 'answer', 'rating']);
  final val = FoundationReviewInput(
    operationId: $checkedConvert('operation_id', (v) => v as String),
    answer: $checkedConvert('answer', (v) => v as String),
    rating: $checkedConvert('rating', (v) => (v as num).toInt()),
  );
  return val;
}, fieldKeyMap: const {'operationId': 'operation_id'});

Map<String, dynamic> _$FoundationReviewInputToJson(
  FoundationReviewInput instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'answer': instance.answer,
  'rating': instance.rating,
};
