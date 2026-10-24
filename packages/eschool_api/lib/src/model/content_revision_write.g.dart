// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'content_revision_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ContentRevisionWrite _$ContentRevisionWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'ContentRevisionWrite',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const ['operation_id', 'item_revision', 'version_revision'],
    );
    final val = ContentRevisionWrite(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      itemRevision: $checkedConvert('item_revision', (v) => (v as num).toInt()),
      versionRevision: $checkedConvert(
        'version_revision',
        (v) => (v as num).toInt(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'operationId': 'operation_id',
    'itemRevision': 'item_revision',
    'versionRevision': 'version_revision',
  },
);

Map<String, dynamic> _$ContentRevisionWriteToJson(
  ContentRevisionWrite instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'item_revision': instance.itemRevision,
  'version_revision': instance.versionRevision,
};
