// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'get_wellbeing_report_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GetWellbeingReportResponse _$GetWellbeingReportResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('GetWellbeingReportResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = GetWellbeingReportResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => WellbeingReport.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$GetWellbeingReportResponseToJson(
  GetWellbeingReportResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
