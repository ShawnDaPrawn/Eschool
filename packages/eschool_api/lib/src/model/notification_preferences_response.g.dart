// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'notification_preferences_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

NotificationPreferencesResponse _$NotificationPreferencesResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('NotificationPreferencesResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = NotificationPreferencesResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => (v as List<dynamic>)
          .map(
            (e) =>
                NotificationTypePreference.fromJson(e as Map<String, dynamic>),
          )
          .toList(),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$NotificationPreferencesResponseToJson(
  NotificationPreferencesResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.map((e) => e.toJson()).toList(),
  'code': instance.code,
};
