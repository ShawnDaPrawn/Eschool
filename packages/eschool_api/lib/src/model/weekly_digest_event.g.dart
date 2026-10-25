// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'weekly_digest_event.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WeeklyDigestEvent _$WeeklyDigestEventFromJson(Map<String, dynamic> json) =>
    $checkedCreate('WeeklyDigestEvent', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['event_id', 'date', 'title']);
      final val = WeeklyDigestEvent(
        eventId: $checkedConvert('event_id', (v) => (v as num).toInt()),
        date: $checkedConvert('date', (v) => v as String),
        title: $checkedConvert('title', (v) => v as String),
      );
      return val;
    }, fieldKeyMap: const {'eventId': 'event_id'});

Map<String, dynamic> _$WeeklyDigestEventToJson(WeeklyDigestEvent instance) =>
    <String, dynamic>{
      'event_id': instance.eventId,
      'date': instance.date,
      'title': instance.title,
    };
