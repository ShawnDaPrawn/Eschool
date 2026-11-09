// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'calendar_feed_created_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CalendarFeedCreatedResponse _$CalendarFeedCreatedResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('CalendarFeedCreatedResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = CalendarFeedCreatedResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) =>
          CalendarFeedCreatedResponseData.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$CalendarFeedCreatedResponseToJson(
  CalendarFeedCreatedResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
