// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'incident_reply_receipt.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

IncidentReplyReceipt _$IncidentReplyReceiptFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'IncidentReplyReceipt',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const ['posted', 'operation_id', 'created_at'],
    );
    final val = IncidentReplyReceipt(
      posted: $checkedConvert('posted', (v) => v as bool),
      operationId: $checkedConvert('operation_id', (v) => v as String),
      createdAt: $checkedConvert(
        'created_at',
        (v) => v == null ? null : DateTime.parse(v as String),
      ),
    );
    return val;
  },
  fieldKeyMap: const {'operationId': 'operation_id', 'createdAt': 'created_at'},
);

Map<String, dynamic> _$IncidentReplyReceiptToJson(
  IncidentReplyReceipt instance,
) => <String, dynamic>{
  'posted': instance.posted,
  'operation_id': instance.operationId,
  'created_at': instance.createdAt?.toIso8601String(),
};
