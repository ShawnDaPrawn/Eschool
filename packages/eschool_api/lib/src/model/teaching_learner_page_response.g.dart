// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'teaching_learner_page_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TeachingLearnerPageResponse _$TeachingLearnerPageResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('TeachingLearnerPageResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = TeachingLearnerPageResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => TeachingLearnerPage.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$TeachingLearnerPageResponseCodeEnumEnumMap,
        v,
        unknownValue: TeachingLearnerPageResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$TeachingLearnerPageResponseToJson(
  TeachingLearnerPageResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _$TeachingLearnerPageResponseCodeEnumEnumMap[instance.code]!,
};

const _$TeachingLearnerPageResponseCodeEnumEnumMap = {
  TeachingLearnerPageResponseCodeEnum.number200: 200,
  TeachingLearnerPageResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
