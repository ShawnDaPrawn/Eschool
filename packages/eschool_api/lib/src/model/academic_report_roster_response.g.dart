// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_report_roster_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicReportRosterResponse _$AcademicReportRosterResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicReportRosterResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = AcademicReportRosterResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => AcademicReportRosterData.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$AcademicReportRosterResponseToJson(
  AcademicReportRosterResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
