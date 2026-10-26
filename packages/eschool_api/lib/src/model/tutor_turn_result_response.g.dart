// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'tutor_turn_result_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TutorTurnResultResponse _$TutorTurnResultResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('TutorTurnResultResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = TutorTurnResultResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => TutorTurnResult.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$TutorTurnResultResponseToJson(
  TutorTurnResultResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
