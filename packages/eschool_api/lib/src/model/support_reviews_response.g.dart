// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'support_reviews_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SupportReviewsResponse _$SupportReviewsResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SupportReviewsResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = SupportReviewsResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => SupportReviews.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$SupportReviewsResponseToJson(
  SupportReviewsResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
