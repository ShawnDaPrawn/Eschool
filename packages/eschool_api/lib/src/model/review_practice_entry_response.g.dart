// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'review_practice_entry_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ReviewPracticeEntryResponse _$ReviewPracticeEntryResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ReviewPracticeEntryResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = ReviewPracticeEntryResponse(
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

Map<String, dynamic> _$ReviewPracticeEntryResponseToJson(
  ReviewPracticeEntryResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
