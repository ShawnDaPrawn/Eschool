// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'progress_roster_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ProgressRosterResponse _$ProgressRosterResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ProgressRosterResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = ProgressRosterResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => ProgressRoster.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$ProgressRosterResponseToJson(
  ProgressRosterResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
