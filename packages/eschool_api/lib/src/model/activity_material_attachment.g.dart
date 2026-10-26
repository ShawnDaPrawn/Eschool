// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'activity_material_attachment.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ActivityMaterialAttachment _$ActivityMaterialAttachmentFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'ActivityMaterialAttachment',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'activity_group_id',
        'material_id',
        'name',
        'mime',
        'bytes',
        'revision',
        'removed',
      ],
    );
    final val = ActivityMaterialAttachment(
      id: $checkedConvert('id', (v) => v as String),
      activityGroupId: $checkedConvert(
        'activity_group_id',
        (v) => (v as num).toInt(),
      ),
      materialId: $checkedConvert('material_id', (v) => (v as num).toInt()),
      name: $checkedConvert('name', (v) => v as String),
      mime: $checkedConvert(
        'mime',
        (v) => $enumDecode(
          _$ActivityMaterialAttachmentMimeEnumEnumMap,
          v,
          unknownValue:
              ActivityMaterialAttachmentMimeEnum.unknownDefaultOpenApi,
        ),
      ),
      bytes: $checkedConvert('bytes', (v) => (v as num).toInt()),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      removed: $checkedConvert('removed', (v) => v as bool),
    );
    return val;
  },
  fieldKeyMap: const {
    'activityGroupId': 'activity_group_id',
    'materialId': 'material_id',
  },
);

Map<String, dynamic> _$ActivityMaterialAttachmentToJson(
  ActivityMaterialAttachment instance,
) => <String, dynamic>{
  'id': instance.id,
  'activity_group_id': instance.activityGroupId,
  'material_id': instance.materialId,
  'name': instance.name,
  'mime': _$ActivityMaterialAttachmentMimeEnumEnumMap[instance.mime]!,
  'bytes': instance.bytes,
  'revision': instance.revision,
  'removed': instance.removed,
};

const _$ActivityMaterialAttachmentMimeEnumEnumMap = {
  ActivityMaterialAttachmentMimeEnum.applicationSlashPdf: 'application/pdf',
  ActivityMaterialAttachmentMimeEnum.imageSlashJpeg: 'image/jpeg',
  ActivityMaterialAttachmentMimeEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
