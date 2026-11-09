// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'get_practice_marking_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GetPracticeMarkingResponse _$GetPracticeMarkingResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('GetPracticeMarkingResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = GetPracticeMarkingResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => PracticeMarkingQueue.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$GetPracticeMarkingResponseToJson(
  GetPracticeMarkingResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
