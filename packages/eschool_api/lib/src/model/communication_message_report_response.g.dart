// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'communication_message_report_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CommunicationMessageReportResponse _$CommunicationMessageReportResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('CommunicationMessageReportResponse', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = CommunicationMessageReportResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => CommunicationMessageReport.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$CommunicationMessageReportResponseToJson(
  CommunicationMessageReportResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
