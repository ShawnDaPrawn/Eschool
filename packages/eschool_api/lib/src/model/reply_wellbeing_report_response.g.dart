// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'reply_wellbeing_report_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ReplyWellbeingReportResponse _$ReplyWellbeingReportResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ReplyWellbeingReportResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = ReplyWellbeingReportResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => WellbeingReportReplyReceipt.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$ReplyWellbeingReportResponseToJson(
  ReplyWellbeingReportResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
