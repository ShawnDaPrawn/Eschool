// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_report_access_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicReportAccessResponse _$AcademicReportAccessResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicReportAccessResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = AcademicReportAccessResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => AcademicReportAccessData.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$AcademicReportAccessResponseToJson(
  AcademicReportAccessResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
