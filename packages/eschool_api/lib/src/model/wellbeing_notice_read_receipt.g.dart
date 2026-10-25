// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wellbeing_notice_read_receipt.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WellbeingNoticeReadReceipt _$WellbeingNoticeReadReceiptFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('WellbeingNoticeReadReceipt', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['id', 'read_at']);
  final val = WellbeingNoticeReadReceipt(
    id: $checkedConvert('id', (v) => (v as num).toInt()),
    readAt: $checkedConvert('read_at', (v) => DateTime.parse(v as String)),
  );
  return val;
}, fieldKeyMap: const {'readAt': 'read_at'});

Map<String, dynamic> _$WellbeingNoticeReadReceiptToJson(
  WellbeingNoticeReadReceipt instance,
) => <String, dynamic>{
  'id': instance.id,
  'read_at': instance.readAt.toIso8601String(),
};
