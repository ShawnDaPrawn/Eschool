// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'content_version.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ContentVersion _$ContentVersionFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'ContentVersion',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'id',
            'content_item_id',
            'number',
            'state',
            'selection',
            'markdown',
            'revision',
            'failure_code',
            'duration_seconds',
            'language',
            'created_at',
            'published_at',
            'source',
          ],
        );
        final val = ContentVersion(
          id: $checkedConvert('id', (v) => (v as num).toInt()),
          contentItemId: $checkedConvert(
            'content_item_id',
            (v) => (v as num).toInt(),
          ),
          number: $checkedConvert('number', (v) => (v as num).toInt()),
          state: $checkedConvert(
            'state',
            (v) => $enumDecode(
              _$ContentVersionStateEnumEnumMap,
              v,
              unknownValue: ContentVersionStateEnum.unknownDefaultOpenApi,
            ),
          ),
          selection: $checkedConvert(
            'selection',
            (v) => ContentSelection.fromJson(v as Map<String, dynamic>),
          ),
          markdown: $checkedConvert('markdown', (v) => v as String?),
          revision: $checkedConvert('revision', (v) => (v as num).toInt()),
          failureCode: $checkedConvert('failure_code', (v) => v as String?),
          durationSeconds: $checkedConvert(
            'duration_seconds',
            (v) => (v as num?)?.toInt(),
          ),
          language: $checkedConvert('language', (v) => v as String?),
          createdAt: $checkedConvert(
            'created_at',
            (v) => DateTime.parse(v as String),
          ),
          publishedAt: $checkedConvert(
            'published_at',
            (v) => v == null ? null : DateTime.parse(v as String),
          ),
          source_: $checkedConvert(
            'source',
            (v) => ContentSource.fromJson(v as Map<String, dynamic>),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'contentItemId': 'content_item_id',
        'failureCode': 'failure_code',
        'durationSeconds': 'duration_seconds',
        'createdAt': 'created_at',
        'publishedAt': 'published_at',
        'source_': 'source',
      },
    );

Map<String, dynamic> _$ContentVersionToJson(ContentVersion instance) =>
    <String, dynamic>{
      'id': instance.id,
      'content_item_id': instance.contentItemId,
      'number': instance.number,
      'state': _$ContentVersionStateEnumEnumMap[instance.state]!,
      'selection': instance.selection.toJson(),
      'markdown': instance.markdown,
      'revision': instance.revision,
      'failure_code': instance.failureCode,
      'duration_seconds': instance.durationSeconds,
      'language': instance.language,
      'created_at': instance.createdAt.toIso8601String(),
      'published_at': instance.publishedAt?.toIso8601String(),
      'source': instance.source_.toJson(),
    };

const _$ContentVersionStateEnumEnumMap = {
  ContentVersionStateEnum.queued: 'queued',
  ContentVersionStateEnum.scanning: 'scanning',
  ContentVersionStateEnum.converting: 'converting',
  ContentVersionStateEnum.draft: 'draft',
  ContentVersionStateEnum.published: 'published',
  ContentVersionStateEnum.failed: 'failed',
  ContentVersionStateEnum.quarantined: 'quarantined',
  ContentVersionStateEnum.superseded: 'superseded',
  ContentVersionStateEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
