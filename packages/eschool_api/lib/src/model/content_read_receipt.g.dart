// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'content_read_receipt.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ContentReadReceipt _$ContentReadReceiptFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ContentReadReceipt', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['version_id', 'learner_id', 'read']);
  final val = ContentReadReceipt(
    versionId: $checkedConvert('version_id', (v) => (v as num).toInt()),
    learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
    read: $checkedConvert('read', (v) => v as bool),
  );
  return val;
}, fieldKeyMap: const {'versionId': 'version_id', 'learnerId': 'learner_id'});

Map<String, dynamic> _$ContentReadReceiptToJson(ContentReadReceipt instance) =>
    <String, dynamic>{
      'version_id': instance.versionId,
      'learner_id': instance.learnerId,
      'read': instance.read,
    };
