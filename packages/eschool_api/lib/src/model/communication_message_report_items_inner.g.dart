// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'communication_message_report_items_inner.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CommunicationMessageReportItemsInner
_$CommunicationMessageReportItemsInnerFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('CommunicationMessageReportItemsInner', json, (
  $checkedConvert,
) {
  $checkKeys(
    json,
    requiredKeys: const ['name', 'read_at', 'notice', 'email', 'push'],
  );
  final val = CommunicationMessageReportItemsInner(
    name: $checkedConvert('name', (v) => v as String),
    readAt: $checkedConvert(
      'read_at',
      (v) => v == null ? null : DateTime.parse(v as String),
    ),
    notice: $checkedConvert(
      'notice',
      (v) => $enumDecode(
        _$CommunicationMessageReportItemsInnerNoticeEnumEnumMap,
        v,
        unknownValue: CommunicationMessageReportItemsInnerNoticeEnum
            .unknownDefaultOpenApi,
      ),
    ),
    email: $checkedConvert(
      'email',
      (v) => $enumDecodeNullable(
        _$CommunicationMessageReportItemsInnerEmailEnumEnumMap,
        v,
        unknownValue:
            CommunicationMessageReportItemsInnerEmailEnum.unknownDefaultOpenApi,
      ),
    ),
    push: $checkedConvert(
      'push',
      (v) => $enumDecodeNullable(
        _$CommunicationMessageReportItemsInnerPushEnumEnumMap,
        v,
        unknownValue:
            CommunicationMessageReportItemsInnerPushEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
}, fieldKeyMap: const {'readAt': 'read_at'});

Map<String, dynamic> _$CommunicationMessageReportItemsInnerToJson(
  CommunicationMessageReportItemsInner instance,
) => <String, dynamic>{
  'name': instance.name,
  'read_at': instance.readAt?.toIso8601String(),
  'notice':
      _$CommunicationMessageReportItemsInnerNoticeEnumEnumMap[instance.notice]!,
  'email':
      _$CommunicationMessageReportItemsInnerEmailEnumEnumMap[instance.email],
  'push': _$CommunicationMessageReportItemsInnerPushEnumEnumMap[instance.push],
};

const _$CommunicationMessageReportItemsInnerNoticeEnumEnumMap = {
  CommunicationMessageReportItemsInnerNoticeEnum.pending: 'pending',
  CommunicationMessageReportItemsInnerNoticeEnum.queued: 'queued',
  CommunicationMessageReportItemsInnerNoticeEnum.none: 'none',
  CommunicationMessageReportItemsInnerNoticeEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$CommunicationMessageReportItemsInnerEmailEnumEnumMap = {
  CommunicationMessageReportItemsInnerEmailEnum.delivered: 'delivered',
  CommunicationMessageReportItemsInnerEmailEnum.sent: 'sent',
  CommunicationMessageReportItemsInnerEmailEnum.queued: 'queued',
  CommunicationMessageReportItemsInnerEmailEnum.bounced: 'bounced',
  CommunicationMessageReportItemsInnerEmailEnum.complained: 'complained',
  CommunicationMessageReportItemsInnerEmailEnum.failed: 'failed',
  CommunicationMessageReportItemsInnerEmailEnum.suppressed: 'suppressed',
  CommunicationMessageReportItemsInnerEmailEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$CommunicationMessageReportItemsInnerPushEnumEnumMap = {
  CommunicationMessageReportItemsInnerPushEnum.delivered: 'delivered',
  CommunicationMessageReportItemsInnerPushEnum.sent: 'sent',
  CommunicationMessageReportItemsInnerPushEnum.queued: 'queued',
  CommunicationMessageReportItemsInnerPushEnum.bounced: 'bounced',
  CommunicationMessageReportItemsInnerPushEnum.complained: 'complained',
  CommunicationMessageReportItemsInnerPushEnum.failed: 'failed',
  CommunicationMessageReportItemsInnerPushEnum.suppressed: 'suppressed',
  CommunicationMessageReportItemsInnerPushEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
