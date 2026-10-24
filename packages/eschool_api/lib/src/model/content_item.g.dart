// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'content_item.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ContentItem _$ContentItemFromJson(Map<String, dynamic> json) => $checkedCreate(
  'ContentItem',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'can_write',
        'active',
        'revision',
        'author_id',
        'draft',
        'published',
        'page',
        'last_page',
        'versions',
      ],
    );
    final val = ContentItem(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      canWrite: $checkedConvert('can_write', (v) => v as bool),
      active: $checkedConvert('active', (v) => v as bool),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      authorId: $checkedConvert('author_id', (v) => (v as num).toInt()),
      draft: $checkedConvert(
        'draft',
        (v) => v == null
            ? null
            : ContentVersion.fromJson(v as Map<String, dynamic>),
      ),
      published: $checkedConvert(
        'published',
        (v) => v == null
            ? null
            : ContentVersion.fromJson(v as Map<String, dynamic>),
      ),
      page: $checkedConvert('page', (v) => (v as num).toInt()),
      lastPage: $checkedConvert('last_page', (v) => (v as num).toInt()),
      versions: $checkedConvert(
        'versions',
        (v) => (v as List<dynamic>)
            .map((e) => ContentHistory.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'canWrite': 'can_write',
    'authorId': 'author_id',
    'lastPage': 'last_page',
  },
);

Map<String, dynamic> _$ContentItemToJson(ContentItem instance) =>
    <String, dynamic>{
      'id': instance.id,
      'can_write': instance.canWrite,
      'active': instance.active,
      'revision': instance.revision,
      'author_id': instance.authorId,
      'draft': instance.draft?.toJson(),
      'published': instance.published?.toJson(),
      'page': instance.page,
      'last_page': instance.lastPage,
      'versions': instance.versions.map((e) => e.toJson()).toList(),
    };
