// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'communication_delivery_counts.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CommunicationDeliveryCounts _$CommunicationDeliveryCountsFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('CommunicationDeliveryCounts', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const [
      'delivered',
      'sent',
      'queued',
      'bounced',
      'complained',
      'failed',
      'suppressed',
    ],
  );
  final val = CommunicationDeliveryCounts(
    delivered: $checkedConvert('delivered', (v) => (v as num).toInt()),
    sent: $checkedConvert('sent', (v) => (v as num).toInt()),
    queued: $checkedConvert('queued', (v) => (v as num).toInt()),
    bounced: $checkedConvert('bounced', (v) => (v as num).toInt()),
    complained: $checkedConvert('complained', (v) => (v as num).toInt()),
    failed: $checkedConvert('failed', (v) => (v as num).toInt()),
    suppressed: $checkedConvert('suppressed', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$CommunicationDeliveryCountsToJson(
  CommunicationDeliveryCounts instance,
) => <String, dynamic>{
  'delivered': instance.delivered,
  'sent': instance.sent,
  'queued': instance.queued,
  'bounced': instance.bounced,
  'complained': instance.complained,
  'failed': instance.failed,
  'suppressed': instance.suppressed,
};
