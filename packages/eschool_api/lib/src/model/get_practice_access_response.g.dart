// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'get_practice_access_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GetPracticeAccessResponse _$GetPracticeAccessResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('GetPracticeAccessResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = GetPracticeAccessResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => PracticeAccess.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$GetPracticeAccessResponseToJson(
  GetPracticeAccessResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
