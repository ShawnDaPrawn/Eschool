// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'content_reader.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ContentReader _$ContentReaderFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'ContentReader',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'id',
            'content_item_id',
            'learner_id',
            'school_id',
            'reader_id',
            'title',
            'summary',
            'markdown',
            'body_sha256',
            'published_at',
            'topics',
            'server_time',
            'offline_until',
          ],
        );
        final val = ContentReader(
          id: $checkedConvert('id', (v) => (v as num).toInt()),
          contentItemId: $checkedConvert(
            'content_item_id',
            (v) => (v as num).toInt(),
          ),
          learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
          schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
          readerId: $checkedConvert('reader_id', (v) => (v as num).toInt()),
          title: $checkedConvert('title', (v) => v as String),
          summary: $checkedConvert('summary', (v) => v as String),
          markdown: $checkedConvert('markdown', (v) => v as String),
          bodySha256: $checkedConvert('body_sha256', (v) => v as String),
          publishedAt: $checkedConvert(
            'published_at',
            (v) => DateTime.parse(v as String),
          ),
          topics: $checkedConvert(
            'topics',
            (v) => (v as List<dynamic>)
                .map((e) => ContentTopic.fromJson(e as Map<String, dynamic>))
                .toList(),
          ),
          serverTime: $checkedConvert(
            'server_time',
            (v) => DateTime.parse(v as String),
          ),
          offlineUntil: $checkedConvert(
            'offline_until',
            (v) => DateTime.parse(v as String),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'contentItemId': 'content_item_id',
        'learnerId': 'learner_id',
        'schoolId': 'school_id',
        'readerId': 'reader_id',
        'bodySha256': 'body_sha256',
        'publishedAt': 'published_at',
        'serverTime': 'server_time',
        'offlineUntil': 'offline_until',
      },
    );

Map<String, dynamic> _$ContentReaderToJson(ContentReader instance) =>
    <String, dynamic>{
      'id': instance.id,
      'content_item_id': instance.contentItemId,
      'learner_id': instance.learnerId,
      'school_id': instance.schoolId,
      'reader_id': instance.readerId,
      'title': instance.title,
      'summary': instance.summary,
      'markdown': instance.markdown,
      'body_sha256': instance.bodySha256,
      'published_at': instance.publishedAt.toIso8601String(),
      'topics': instance.topics.map((e) => e.toJson()).toList(),
      'server_time': instance.serverTime.toIso8601String(),
      'offline_until': instance.offlineUntil.toIso8601String(),
    };
