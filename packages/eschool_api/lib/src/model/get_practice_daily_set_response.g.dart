// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'get_practice_daily_set_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GetPracticeDailySetResponse _$GetPracticeDailySetResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('GetPracticeDailySetResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = GetPracticeDailySetResponse(
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

Map<String, dynamic> _$GetPracticeDailySetResponseToJson(
  GetPracticeDailySetResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
