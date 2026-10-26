// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'activity_material_attachment_page.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ActivityMaterialAttachmentPage _$ActivityMaterialAttachmentPageFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'ActivityMaterialAttachmentPage',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'items',
        'material_revision',
        'can_upload',
        'can_remove',
      ],
    );
    final val = ActivityMaterialAttachmentPage(
      items: $checkedConvert(
        'items',
        (v) => (v as List<dynamic>)
            .map(
              (e) => ActivityMaterialAttachment.fromJson(
                e as Map<String, dynamic>,
              ),
            )
            .toList(),
      ),
      materialRevision: $checkedConvert(
        'material_revision',
        (v) => (v as num).toInt(),
      ),
      canUpload: $checkedConvert('can_upload', (v) => v as bool),
      canRemove: $checkedConvert('can_remove', (v) => v as bool),
    );
    return val;
  },
  fieldKeyMap: const {
    'materialRevision': 'material_revision',
    'canUpload': 'can_upload',
    'canRemove': 'can_remove',
  },
);

Map<String, dynamic> _$ActivityMaterialAttachmentPageToJson(
  ActivityMaterialAttachmentPage instance,
) => <String, dynamic>{
  'items': instance.items.map((e) => e.toJson()).toList(),
  'material_revision': instance.materialRevision,
  'can_upload': instance.canUpload,
  'can_remove': instance.canRemove,
};
