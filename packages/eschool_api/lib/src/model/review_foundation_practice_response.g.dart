// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'review_foundation_practice_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ReviewFoundationPracticeResponse _$ReviewFoundationPracticeResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ReviewFoundationPracticeResponse', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = ReviewFoundationPracticeResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => PracticeReview.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$ReviewFoundationPracticeResponseToJson(
  ReviewFoundationPracticeResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
