// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'bursary_review_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

BursaryReviewResponse _$BursaryReviewResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('BursaryReviewResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = BursaryReviewResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => BursaryReview.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$BursaryReviewResponseCodeEnumEnumMap,
        v,
        unknownValue: BursaryReviewResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$BursaryReviewResponseToJson(
  BursaryReviewResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _$BursaryReviewResponseCodeEnumEnumMap[instance.code]!,
};

const _$BursaryReviewResponseCodeEnumEnumMap = {
  BursaryReviewResponseCodeEnum.number200: 200,
  BursaryReviewResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
