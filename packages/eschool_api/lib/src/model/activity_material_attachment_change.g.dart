// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'activity_material_attachment_change.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ActivityMaterialAttachmentChange _$ActivityMaterialAttachmentChangeFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'ActivityMaterialAttachmentChange',
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
        'material_revision',
      ],
    );
    final val = ActivityMaterialAttachmentChange(
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
          _$ActivityMaterialAttachmentChangeMimeEnumEnumMap,
          v,
          unknownValue:
              ActivityMaterialAttachmentChangeMimeEnum.unknownDefaultOpenApi,
        ),
      ),
      bytes: $checkedConvert('bytes', (v) => (v as num).toInt()),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      removed: $checkedConvert('removed', (v) => v as bool),
      materialRevision: $checkedConvert(
        'material_revision',
        (v) => (v as num).toInt(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'activityGroupId': 'activity_group_id',
    'materialId': 'material_id',
    'materialRevision': 'material_revision',
  },
);

Map<String, dynamic> _$ActivityMaterialAttachmentChangeToJson(
  ActivityMaterialAttachmentChange instance,
) => <String, dynamic>{
  'id': instance.id,
  'activity_group_id': instance.activityGroupId,
  'material_id': instance.materialId,
  'name': instance.name,
  'mime': _$ActivityMaterialAttachmentChangeMimeEnumEnumMap[instance.mime]!,
  'bytes': instance.bytes,
  'revision': instance.revision,
  'removed': instance.removed,
  'material_revision': instance.materialRevision,
};

const _$ActivityMaterialAttachmentChangeMimeEnumEnumMap = {
  ActivityMaterialAttachmentChangeMimeEnum.applicationSlashPdf:
      'application/pdf',
  ActivityMaterialAttachmentChangeMimeEnum.imageSlashJpeg: 'image/jpeg',
  ActivityMaterialAttachmentChangeMimeEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
