// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'communication_message_report.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CommunicationMessageReport _$CommunicationMessageReportFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'CommunicationMessageReport',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'title',
        'published_at',
        'recipient_count',
        'read_count',
        'not_notified',
        'channels',
        'page',
        'last_page',
        'items',
      ],
    );
    final val = CommunicationMessageReport(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      title: $checkedConvert('title', (v) => v as String),
      publishedAt: $checkedConvert(
        'published_at',
        (v) => DateTime.parse(v as String),
      ),
      recipientCount: $checkedConvert(
        'recipient_count',
        (v) => (v as num).toInt(),
      ),
      readCount: $checkedConvert('read_count', (v) => (v as num).toInt()),
      notNotified: $checkedConvert('not_notified', (v) => (v as num).toInt()),
      channels: $checkedConvert(
        'channels',
        (v) => CommunicationMessageReportChannels.fromJson(
          v as Map<String, dynamic>,
        ),
      ),
      page: $checkedConvert('page', (v) => (v as num).toInt()),
      lastPage: $checkedConvert('last_page', (v) => (v as num).toInt()),
      items: $checkedConvert(
        'items',
        (v) => (v as List<dynamic>)
            .map(
              (e) => CommunicationMessageReportItemsInner.fromJson(
                e as Map<String, dynamic>,
              ),
            )
            .toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'publishedAt': 'published_at',
    'recipientCount': 'recipient_count',
    'readCount': 'read_count',
    'notNotified': 'not_notified',
    'lastPage': 'last_page',
  },
);

Map<String, dynamic> _$CommunicationMessageReportToJson(
  CommunicationMessageReport instance,
) => <String, dynamic>{
  'id': instance.id,
  'title': instance.title,
  'published_at': instance.publishedAt.toIso8601String(),
  'recipient_count': instance.recipientCount,
  'read_count': instance.readCount,
  'not_notified': instance.notNotified,
  'channels': instance.channels.toJson(),
  'page': instance.page,
  'last_page': instance.lastPage,
  'items': instance.items.map((e) => e.toJson()).toList(),
};
