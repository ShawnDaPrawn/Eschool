// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_report_configuration_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicReportConfigurationResponse
_$AcademicReportConfigurationResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicReportConfigurationResponse', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = AcademicReportConfigurationResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) =>
          AcademicReportConfigurationData.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$AcademicReportConfigurationResponseToJson(
  AcademicReportConfigurationResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
