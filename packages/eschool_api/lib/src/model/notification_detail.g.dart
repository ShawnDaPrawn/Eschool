// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'notification_detail.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

NotificationDetail _$NotificationDetailFromJson(Map<String, dynamic> json) =>
    $checkedCreate('NotificationDetail', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const [
          'id',
          'type',
          'title',
          'excerpt',
          'target',
          'has_body',
          'date',
          'body',
        ],
      );
      final val = NotificationDetail(
        id: $checkedConvert('id', (v) => (v as num).toInt()),
        type: $checkedConvert('type', (v) => v as String),
        title: $checkedConvert('title', (v) => v as String),
        excerpt: $checkedConvert('excerpt', (v) => v as String),
        target: $checkedConvert('target', (v) => v as String),
        hasBody: $checkedConvert('has_body', (v) => v as bool),
        date: $checkedConvert('date', (v) => DateTime.parse(v as String)),
        body: $checkedConvert('body', (v) => v as String),
      );
      return val;
    }, fieldKeyMap: const {'hasBody': 'has_body'});

Map<String, dynamic> _$NotificationDetailToJson(NotificationDetail instance) =>
    <String, dynamic>{
      'id': instance.id,
      'type': instance.type,
      'title': instance.title,
      'excerpt': instance.excerpt,
      'target': instance.target,
      'has_body': instance.hasBody,
      'date': instance.date.toIso8601String(),
      'body': instance.body,
    };
