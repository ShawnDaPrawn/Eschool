// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'content_receipt.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ContentReceipt _$ContentReceiptFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'ContentReceipt',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'id',
            'version_id',
            'item_revision',
            'version_revision',
          ],
        );
        final val = ContentReceipt(
          id: $checkedConvert('id', (v) => (v as num).toInt()),
          versionId: $checkedConvert('version_id', (v) => (v as num?)?.toInt()),
          itemRevision: $checkedConvert(
            'item_revision',
            (v) => (v as num).toInt(),
          ),
          versionRevision: $checkedConvert(
            'version_revision',
            (v) => (v as num?)?.toInt(),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'versionId': 'version_id',
        'itemRevision': 'item_revision',
        'versionRevision': 'version_revision',
      },
    );

Map<String, dynamic> _$ContentReceiptToJson(ContentReceipt instance) =>
    <String, dynamic>{
      'id': instance.id,
      'version_id': instance.versionId,
      'item_revision': instance.itemRevision,
      'version_revision': instance.versionRevision,
    };
