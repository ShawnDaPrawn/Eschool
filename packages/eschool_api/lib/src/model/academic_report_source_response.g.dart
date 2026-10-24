// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_report_source_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicReportSourceResponse _$AcademicReportSourceResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicReportSourceResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = AcademicReportSourceResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => AcademicReportSourceData.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$AcademicReportSourceResponseToJson(
  AcademicReportSourceResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
