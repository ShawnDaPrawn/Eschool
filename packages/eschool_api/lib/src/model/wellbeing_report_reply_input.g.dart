// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wellbeing_report_reply_input.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WellbeingReportReplyInput _$WellbeingReportReplyInputFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('WellbeingReportReplyInput', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const [
      'operation_id',
      'revision',
      'body',
      'internal',
      'status',
    ],
  );
  final val = WellbeingReportReplyInput(
    operationId: $checkedConvert('operation_id', (v) => v as String),
    revision: $checkedConvert('revision', (v) => (v as num).toInt()),
    body: $checkedConvert('body', (v) => v as String),
    internal: $checkedConvert('internal', (v) => v as bool),
    status: $checkedConvert(
      'status',
      (v) => $enumDecode(
        _$WellbeingReportReplyInputStatusEnumEnumMap,
        v,
        unknownValue: WellbeingReportReplyInputStatusEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
}, fieldKeyMap: const {'operationId': 'operation_id'});

Map<String, dynamic> _$WellbeingReportReplyInputToJson(
  WellbeingReportReplyInput instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'revision': instance.revision,
  'body': instance.body,
  'internal': instance.internal,
  'status': _$WellbeingReportReplyInputStatusEnumEnumMap[instance.status]!,
};

const _$WellbeingReportReplyInputStatusEnumEnumMap = {
  WellbeingReportReplyInputStatusEnum.open: 'open',
  WellbeingReportReplyInputStatusEnum.triaged: 'triaged',
  WellbeingReportReplyInputStatusEnum.resolved: 'resolved',
  WellbeingReportReplyInputStatusEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
