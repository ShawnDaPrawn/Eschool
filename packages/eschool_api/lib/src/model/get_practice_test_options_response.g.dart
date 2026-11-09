// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'get_practice_test_options_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GetPracticeTestOptionsResponse _$GetPracticeTestOptionsResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('GetPracticeTestOptionsResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = GetPracticeTestOptionsResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => PracticeTestOptions.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$GetPracticeTestOptionsResponseToJson(
  GetPracticeTestOptionsResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
