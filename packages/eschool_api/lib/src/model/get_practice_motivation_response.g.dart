// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'get_practice_motivation_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GetPracticeMotivationResponse _$GetPracticeMotivationResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('GetPracticeMotivationResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = GetPracticeMotivationResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => PracticeMotivation.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$GetPracticeMotivationResponseToJson(
  GetPracticeMotivationResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
