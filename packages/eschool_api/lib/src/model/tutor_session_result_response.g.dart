// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'tutor_session_result_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TutorSessionResultResponse _$TutorSessionResultResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('TutorSessionResultResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = TutorSessionResultResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => TutorSessionResult.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$TutorSessionResultResponseToJson(
  TutorSessionResultResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
