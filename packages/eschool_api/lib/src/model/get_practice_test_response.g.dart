// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'get_practice_test_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GetPracticeTestResponse _$GetPracticeTestResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('GetPracticeTestResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = GetPracticeTestResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => PracticeTestSitting.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$GetPracticeTestResponseToJson(
  GetPracticeTestResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
