// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'calendar_feed_revoked_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CalendarFeedRevokedResponse _$CalendarFeedRevokedResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('CalendarFeedRevokedResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = CalendarFeedRevokedResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) =>
          CalendarFeedRevokedResponseData.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$CalendarFeedRevokedResponseToJson(
  CalendarFeedRevokedResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
