// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_report_template_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicReportTemplateResponse _$AcademicReportTemplateResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicReportTemplateResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = AcademicReportTemplateResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => AcademicReportTemplate.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$AcademicReportTemplateResponseToJson(
  AcademicReportTemplateResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
