// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'content_list_item.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ContentListItem _$ContentListItemFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'ContentListItem',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'id',
            'title',
            'active',
            'draft_version_id',
            'published_version_id',
            'draft_state',
            'revision',
            'updated_at',
          ],
        );
        final val = ContentListItem(
          id: $checkedConvert('id', (v) => (v as num).toInt()),
          title: $checkedConvert('title', (v) => v as String),
          active: $checkedConvert('active', (v) => v as bool),
          draftVersionId: $checkedConvert(
            'draft_version_id',
            (v) => (v as num?)?.toInt(),
          ),
          publishedVersionId: $checkedConvert(
            'published_version_id',
            (v) => (v as num?)?.toInt(),
          ),
          draftState: $checkedConvert(
            'draft_state',
            (v) => $enumDecodeNullable(
              _$ContentListItemDraftStateEnumEnumMap,
              v,
              unknownValue: ContentListItemDraftStateEnum.unknownDefaultOpenApi,
            ),
          ),
          revision: $checkedConvert('revision', (v) => (v as num).toInt()),
          updatedAt: $checkedConvert(
            'updated_at',
            (v) => DateTime.parse(v as String),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'draftVersionId': 'draft_version_id',
        'publishedVersionId': 'published_version_id',
        'draftState': 'draft_state',
        'updatedAt': 'updated_at',
      },
    );

Map<String, dynamic> _$ContentListItemToJson(
  ContentListItem instance,
) => <String, dynamic>{
  'id': instance.id,
  'title': instance.title,
  'active': instance.active,
  'draft_version_id': instance.draftVersionId,
  'published_version_id': instance.publishedVersionId,
  'draft_state': _$ContentListItemDraftStateEnumEnumMap[instance.draftState],
  'revision': instance.revision,
  'updated_at': instance.updatedAt.toIso8601String(),
};

const _$ContentListItemDraftStateEnumEnumMap = {
  ContentListItemDraftStateEnum.queued: 'queued',
  ContentListItemDraftStateEnum.scanning: 'scanning',
  ContentListItemDraftStateEnum.converting: 'converting',
  ContentListItemDraftStateEnum.draft: 'draft',
  ContentListItemDraftStateEnum.published: 'published',
  ContentListItemDraftStateEnum.failed: 'failed',
  ContentListItemDraftStateEnum.quarantined: 'quarantined',
  ContentListItemDraftStateEnum.superseded: 'superseded',
  ContentListItemDraftStateEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
