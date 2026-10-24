// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_report_read_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicReportReadResponse _$AcademicReportReadResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicReportReadResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = AcademicReportReadResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => AcademicReportReadData.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$AcademicReportReadResponseToJson(
  AcademicReportReadResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
