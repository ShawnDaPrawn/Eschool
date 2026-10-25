// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'activity_material_view.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ActivityMaterialView _$ActivityMaterialViewFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'ActivityMaterialView',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'activity_group_id',
        'author_id',
        'title',
        'markdown',
        'published',
        'revision',
        'html',
      ],
    );
    final val = ActivityMaterialView(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      activityGroupId: $checkedConvert(
        'activity_group_id',
        (v) => (v as num).toInt(),
      ),
      authorId: $checkedConvert('author_id', (v) => (v as num).toInt()),
      title: $checkedConvert('title', (v) => v as String),
      markdown: $checkedConvert('markdown', (v) => v as String),
      published: $checkedConvert('published', (v) => v as bool),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      html: $checkedConvert('html', (v) => v as String),
    );
    return val;
  },
  fieldKeyMap: const {
    'activityGroupId': 'activity_group_id',
    'authorId': 'author_id',
  },
);

Map<String, dynamic> _$ActivityMaterialViewToJson(
  ActivityMaterialView instance,
) => <String, dynamic>{
  'id': instance.id,
  'activity_group_id': instance.activityGroupId,
  'author_id': instance.authorId,
  'title': instance.title,
  'markdown': instance.markdown,
  'published': instance.published,
  'revision': instance.revision,
  'html': instance.html,
};
