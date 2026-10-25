// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'recognition_point_history_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

RecognitionPointHistoryResponse _$RecognitionPointHistoryResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('RecognitionPointHistoryResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = RecognitionPointHistoryResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => RecognitionPointHistory.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$RecognitionPointHistoryResponseToJson(
  RecognitionPointHistoryResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
