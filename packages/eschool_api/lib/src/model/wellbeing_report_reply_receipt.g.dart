// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wellbeing_report_reply_receipt.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WellbeingReportReplyReceipt _$WellbeingReportReplyReceiptFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('WellbeingReportReplyReceipt', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['id', 'revision', 'status', 'thread_id'],
  );
  final val = WellbeingReportReplyReceipt(
    id: $checkedConvert('id', (v) => (v as num).toInt()),
    revision: $checkedConvert('revision', (v) => (v as num).toInt()),
    status: $checkedConvert(
      'status',
      (v) => $enumDecode(
        _$WellbeingReportReplyReceiptStatusEnumEnumMap,
        v,
        unknownValue:
            WellbeingReportReplyReceiptStatusEnum.unknownDefaultOpenApi,
      ),
    ),
    threadId: $checkedConvert('thread_id', (v) => (v as num).toInt()),
  );
  return val;
}, fieldKeyMap: const {'threadId': 'thread_id'});

Map<String, dynamic> _$WellbeingReportReplyReceiptToJson(
  WellbeingReportReplyReceipt instance,
) => <String, dynamic>{
  'id': instance.id,
  'revision': instance.revision,
  'status': _$WellbeingReportReplyReceiptStatusEnumEnumMap[instance.status]!,
  'thread_id': instance.threadId,
};

const _$WellbeingReportReplyReceiptStatusEnumEnumMap = {
  WellbeingReportReplyReceiptStatusEnum.open: 'open',
  WellbeingReportReplyReceiptStatusEnum.triaged: 'triaged',
  WellbeingReportReplyReceiptStatusEnum.resolved: 'resolved',
  WellbeingReportReplyReceiptStatusEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
