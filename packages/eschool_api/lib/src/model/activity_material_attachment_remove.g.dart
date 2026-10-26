// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'activity_material_attachment_remove.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ActivityMaterialAttachmentRemove _$ActivityMaterialAttachmentRemoveFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'ActivityMaterialAttachmentRemove',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const ['operation_id', 'revision', 'material_revision'],
    );
    final val = ActivityMaterialAttachmentRemove(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      materialRevision: $checkedConvert(
        'material_revision',
        (v) => (v as num).toInt(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'operationId': 'operation_id',
    'materialRevision': 'material_revision',
  },
);

Map<String, dynamic> _$ActivityMaterialAttachmentRemoveToJson(
  ActivityMaterialAttachmentRemove instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'revision': instance.revision,
  'material_revision': instance.materialRevision,
};
