// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'notification_inbox_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

NotificationInboxResponse _$NotificationInboxResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('NotificationInboxResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = NotificationInboxResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => NotificationInboxPage.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$NotificationInboxResponseToJson(
  NotificationInboxResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
