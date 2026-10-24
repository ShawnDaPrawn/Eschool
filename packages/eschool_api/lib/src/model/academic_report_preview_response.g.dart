// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_report_preview_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicReportPreviewResponse _$AcademicReportPreviewResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicReportPreviewResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = AcademicReportPreviewResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => AcademicReportPreviewData.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$AcademicReportPreviewResponseToJson(
  AcademicReportPreviewResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
