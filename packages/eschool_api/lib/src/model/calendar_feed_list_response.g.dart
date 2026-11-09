// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'calendar_feed_list_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CalendarFeedListResponse _$CalendarFeedListResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('CalendarFeedListResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = CalendarFeedListResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => CalendarFeedListResponseData.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$CalendarFeedListResponseToJson(
  CalendarFeedListResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
