// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'recognition_house_counters_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

RecognitionHouseCountersResponse _$RecognitionHouseCountersResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('RecognitionHouseCountersResponse', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = RecognitionHouseCountersResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => RecognitionHouseCounters.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$RecognitionHouseCountersResponseToJson(
  RecognitionHouseCountersResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
