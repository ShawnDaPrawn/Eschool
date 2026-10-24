// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'content_publish_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ContentPublishWrite _$ContentPublishWriteFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'ContentPublishWrite',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'operation_id',
            'item_revision',
            'version_revision',
            'fingerprint',
            'reviewed',
          ],
        );
        final val = ContentPublishWrite(
          operationId: $checkedConvert('operation_id', (v) => v as String),
          itemRevision: $checkedConvert(
            'item_revision',
            (v) => (v as num).toInt(),
          ),
          versionRevision: $checkedConvert(
            'version_revision',
            (v) => (v as num).toInt(),
          ),
          fingerprint: $checkedConvert('fingerprint', (v) => v as String),
          reviewed: $checkedConvert('reviewed', (v) => v as bool),
          reason: $checkedConvert('reason', (v) => v as String?),
        );
        return val;
      },
      fieldKeyMap: const {
        'operationId': 'operation_id',
        'itemRevision': 'item_revision',
        'versionRevision': 'version_revision',
      },
    );

Map<String, dynamic> _$ContentPublishWriteToJson(
  ContentPublishWrite instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'item_revision': instance.itemRevision,
  'version_revision': instance.versionRevision,
  'fingerprint': instance.fingerprint,
  'reviewed': instance.reviewed,
  'reason': ?instance.reason,
};
