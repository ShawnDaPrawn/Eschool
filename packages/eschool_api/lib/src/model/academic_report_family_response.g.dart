// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_report_family_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicReportFamilyResponse _$AcademicReportFamilyResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicReportFamilyResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = AcademicReportFamilyResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => AcademicReportFamilyData.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$AcademicReportFamilyResponseToJson(
  AcademicReportFamilyResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
