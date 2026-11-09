// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'fixture_learner_list_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FixtureLearnerListResponse _$FixtureLearnerListResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('FixtureLearnerListResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = FixtureLearnerListResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => FixtureLearnerList.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$FixtureLearnerListResponseToJson(
  FixtureLearnerListResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
