// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'calendar_removed_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CalendarRemovedResponse _$CalendarRemovedResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('CalendarRemovedResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = CalendarRemovedResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert('data', (v) => v),
  );
  return val;
});

Map<String, dynamic> _$CalendarRemovedResponseToJson(
  CalendarRemovedResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data,
};
