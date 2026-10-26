// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'tutor_checkpoint_detail_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TutorCheckpointDetailResponse _$TutorCheckpointDetailResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('TutorCheckpointDetailResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = TutorCheckpointDetailResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => TutorCheckpointDetail.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$TutorCheckpointDetailResponseToJson(
  TutorCheckpointDetailResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
