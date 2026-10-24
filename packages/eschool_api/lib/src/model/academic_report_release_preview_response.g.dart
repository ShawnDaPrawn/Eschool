// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_report_release_preview_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicReportReleasePreviewResponse
_$AcademicReportReleasePreviewResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicReportReleasePreviewResponse', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = AcademicReportReleasePreviewResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) =>
          AcademicReportReleasePreviewData.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$AcademicReportReleasePreviewResponseToJson(
  AcademicReportReleasePreviewResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
