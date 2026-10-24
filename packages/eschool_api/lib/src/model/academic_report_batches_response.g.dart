// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_report_batches_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicReportBatchesResponse _$AcademicReportBatchesResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicReportBatchesResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = AcademicReportBatchesResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => AcademicReportBatchesData.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$AcademicReportBatchesResponseToJson(
  AcademicReportBatchesResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
