// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'diary_reminders_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DiaryRemindersResponse _$DiaryRemindersResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('DiaryRemindersResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = DiaryRemindersResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => DiaryReminders.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$DiaryRemindersResponseToJson(
  DiaryRemindersResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
