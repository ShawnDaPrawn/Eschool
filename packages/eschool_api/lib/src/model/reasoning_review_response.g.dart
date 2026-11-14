// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'reasoning_review_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ReasoningReviewResponse _$ReasoningReviewResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ReasoningReviewResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = ReasoningReviewResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => ReasoningReview.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$ReasoningReviewResponseToJson(
  ReasoningReviewResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
