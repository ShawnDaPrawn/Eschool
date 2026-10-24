// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_report_detail_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicReportDetailResponse _$AcademicReportDetailResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicReportDetailResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = AcademicReportDetailResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => AcademicReportDetailData.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$AcademicReportDetailResponseToJson(
  AcademicReportDetailResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
