// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wellbeing_notice.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WellbeingNotice _$WellbeingNoticeFromJson(Map<String, dynamic> json) =>
    $checkedCreate('WellbeingNotice', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const [
          'id',
          'title',
          'message',
          'link',
          'created_at',
          'read_at',
        ],
      );
      final val = WellbeingNotice(
        id: $checkedConvert('id', (v) => (v as num).toInt()),
        title: $checkedConvert('title', (v) => v as String),
        message: $checkedConvert('message', (v) => v as String),
        link: $checkedConvert('link', (v) => v as String),
        createdAt: $checkedConvert(
          'created_at',
          (v) => DateTime.parse(v as String),
        ),
        readAt: $checkedConvert(
          'read_at',
          (v) => v == null ? null : DateTime.parse(v as String),
        ),
      );
      return val;
    }, fieldKeyMap: const {'createdAt': 'created_at', 'readAt': 'read_at'});

Map<String, dynamic> _$WellbeingNoticeToJson(WellbeingNotice instance) =>
    <String, dynamic>{
      'id': instance.id,
      'title': instance.title,
      'message': instance.message,
      'link': instance.link,
      'created_at': instance.createdAt.toIso8601String(),
      'read_at': instance.readAt?.toIso8601String(),
    };
