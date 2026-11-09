// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'reenrolment_learner_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ReenrolmentLearnerResponse _$ReenrolmentLearnerResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ReenrolmentLearnerResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = ReenrolmentLearnerResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => ReenrolmentLearner.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$ReenrolmentLearnerResponseCodeEnumEnumMap,
        v,
        unknownValue: ReenrolmentLearnerResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$ReenrolmentLearnerResponseToJson(
  ReenrolmentLearnerResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _$ReenrolmentLearnerResponseCodeEnumEnumMap[instance.code]!,
};

const _$ReenrolmentLearnerResponseCodeEnumEnumMap = {
  ReenrolmentLearnerResponseCodeEnum.number200: 200,
  ReenrolmentLearnerResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
