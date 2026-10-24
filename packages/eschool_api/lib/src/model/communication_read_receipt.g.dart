// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'communication_read_receipt.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CommunicationReadReceipt _$CommunicationReadReceiptFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('CommunicationReadReceipt', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['id', 'read_at']);
  final val = CommunicationReadReceipt(
    id: $checkedConvert('id', (v) => (v as num).toInt()),
    readAt: $checkedConvert('read_at', (v) => DateTime.parse(v as String)),
  );
  return val;
}, fieldKeyMap: const {'readAt': 'read_at'});

Map<String, dynamic> _$CommunicationReadReceiptToJson(
  CommunicationReadReceipt instance,
) => <String, dynamic>{
  'id': instance.id,
  'read_at': instance.readAt.toIso8601String(),
};
