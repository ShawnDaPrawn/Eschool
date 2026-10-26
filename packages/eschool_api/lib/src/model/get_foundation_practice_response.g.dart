// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'get_foundation_practice_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GetFoundationPracticeResponse _$GetFoundationPracticeResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('GetFoundationPracticeResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = GetFoundationPracticeResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => PracticeDailySet.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$GetFoundationPracticeResponseToJson(
  GetFoundationPracticeResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
