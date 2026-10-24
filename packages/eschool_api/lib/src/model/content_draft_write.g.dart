// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'content_draft_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ContentDraftWrite _$ContentDraftWriteFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'ContentDraftWrite',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'operation_id',
            'item_revision',
            'version_revision',
            'selection',
            'markdown',
          ],
        );
        final val = ContentDraftWrite(
          operationId: $checkedConvert('operation_id', (v) => v as String),
          itemRevision: $checkedConvert(
            'item_revision',
            (v) => (v as num).toInt(),
          ),
          versionRevision: $checkedConvert(
            'version_revision',
            (v) => (v as num).toInt(),
          ),
          selection: $checkedConvert(
            'selection',
            (v) => ContentSelection.fromJson(v as Map<String, dynamic>),
          ),
          markdown: $checkedConvert('markdown', (v) => v as String),
        );
        return val;
      },
      fieldKeyMap: const {
        'operationId': 'operation_id',
        'itemRevision': 'item_revision',
        'versionRevision': 'version_revision',
      },
    );

Map<String, dynamic> _$ContentDraftWriteToJson(ContentDraftWrite instance) =>
    <String, dynamic>{
      'operation_id': instance.operationId,
      'item_revision': instance.itemRevision,
      'version_revision': instance.versionRevision,
      'selection': instance.selection.toJson(),
      'markdown': instance.markdown,
    };
