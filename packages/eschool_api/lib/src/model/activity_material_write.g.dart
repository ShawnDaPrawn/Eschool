// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'activity_material_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ActivityMaterialWrite _$ActivityMaterialWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ActivityMaterialWrite', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const [
      'operation_id',
      'revision',
      'title',
      'markdown',
      'published',
    ],
  );
  final val = ActivityMaterialWrite(
    operationId: $checkedConvert('operation_id', (v) => v as String),
    revision: $checkedConvert('revision', (v) => (v as num).toInt()),
    title: $checkedConvert('title', (v) => v as String),
    markdown: $checkedConvert('markdown', (v) => v as String),
    published: $checkedConvert('published', (v) => v as bool),
  );
  return val;
}, fieldKeyMap: const {'operationId': 'operation_id'});

Map<String, dynamic> _$ActivityMaterialWriteToJson(
  ActivityMaterialWrite instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'revision': instance.revision,
  'title': instance.title,
  'markdown': instance.markdown,
  'published': instance.published,
};
