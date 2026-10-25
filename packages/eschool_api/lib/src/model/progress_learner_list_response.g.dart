// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'progress_learner_list_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ProgressLearnerListResponse _$ProgressLearnerListResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ProgressLearnerListResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = ProgressLearnerListResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => ProgressLearnerList.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$ProgressLearnerListResponseToJson(
  ProgressLearnerListResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
