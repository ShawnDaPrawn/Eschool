// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'calendar_feed_revoked_response_data.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CalendarFeedRevokedResponseData _$CalendarFeedRevokedResponseDataFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('CalendarFeedRevokedResponseData', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['id', 'revoked']);
  final val = CalendarFeedRevokedResponseData(
    id: $checkedConvert('id', (v) => (v as num).toInt()),
    revoked: $checkedConvert('revoked', (v) => v as bool),
  );
  return val;
});

Map<String, dynamic> _$CalendarFeedRevokedResponseDataToJson(
  CalendarFeedRevokedResponseData instance,
) => <String, dynamic>{'id': instance.id, 'revoked': instance.revoked};
