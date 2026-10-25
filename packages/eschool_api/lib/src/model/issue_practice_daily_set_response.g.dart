// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'issue_practice_daily_set_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

IssuePracticeDailySetResponse _$IssuePracticeDailySetResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('IssuePracticeDailySetResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = IssuePracticeDailySetResponse(
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

Map<String, dynamic> _$IssuePracticeDailySetResponseToJson(
  IssuePracticeDailySetResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
