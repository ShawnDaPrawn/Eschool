// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'content_history.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ContentHistory _$ContentHistoryFromJson(Map<String, dynamic> json) =>
    $checkedCreate('ContentHistory', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['id', 'number', 'state', 'title', 'published_at'],
      );
      final val = ContentHistory(
        id: $checkedConvert('id', (v) => (v as num).toInt()),
        number: $checkedConvert('number', (v) => (v as num).toInt()),
        state: $checkedConvert(
          'state',
          (v) => $enumDecode(
            _$ContentHistoryStateEnumEnumMap,
            v,
            unknownValue: ContentHistoryStateEnum.unknownDefaultOpenApi,
          ),
        ),
        title: $checkedConvert('title', (v) => v as String),
        publishedAt: $checkedConvert(
          'published_at',
          (v) => v == null ? null : DateTime.parse(v as String),
        ),
      );
      return val;
    }, fieldKeyMap: const {'publishedAt': 'published_at'});

Map<String, dynamic> _$ContentHistoryToJson(ContentHistory instance) =>
    <String, dynamic>{
      'id': instance.id,
      'number': instance.number,
      'state': _$ContentHistoryStateEnumEnumMap[instance.state]!,
      'title': instance.title,
      'published_at': instance.publishedAt?.toIso8601String(),
    };

const _$ContentHistoryStateEnumEnumMap = {
  ContentHistoryStateEnum.queued: 'queued',
  ContentHistoryStateEnum.scanning: 'scanning',
  ContentHistoryStateEnum.converting: 'converting',
  ContentHistoryStateEnum.draft: 'draft',
  ContentHistoryStateEnum.published: 'published',
  ContentHistoryStateEnum.failed: 'failed',
  ContentHistoryStateEnum.quarantined: 'quarantined',
  ContentHistoryStateEnum.superseded: 'superseded',
  ContentHistoryStateEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
