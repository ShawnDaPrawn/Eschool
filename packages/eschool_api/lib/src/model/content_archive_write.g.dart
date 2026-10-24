// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'content_archive_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ContentArchiveWrite _$ContentArchiveWriteFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'ContentArchiveWrite',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const ['operation_id', 'item_revision', 'reason'],
        );
        final val = ContentArchiveWrite(
          operationId: $checkedConvert('operation_id', (v) => v as String),
          itemRevision: $checkedConvert(
            'item_revision',
            (v) => (v as num).toInt(),
          ),
          reason: $checkedConvert('reason', (v) => v as String),
        );
        return val;
      },
      fieldKeyMap: const {
        'operationId': 'operation_id',
        'itemRevision': 'item_revision',
      },
    );

Map<String, dynamic> _$ContentArchiveWriteToJson(
  ContentArchiveWrite instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'item_revision': instance.itemRevision,
  'reason': instance.reason,
};
