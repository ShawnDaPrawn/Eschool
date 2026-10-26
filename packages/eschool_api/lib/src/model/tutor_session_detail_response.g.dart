// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'tutor_session_detail_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TutorSessionDetailResponse _$TutorSessionDetailResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('TutorSessionDetailResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = TutorSessionDetailResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => TutorSessionDetail.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$TutorSessionDetailResponseToJson(
  TutorSessionDetailResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
