// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_report_bank_saved_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicReportBankSavedResponse _$AcademicReportBankSavedResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicReportBankSavedResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = AcademicReportBankSavedResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => AcademicReportSavedRevision.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$AcademicReportBankSavedResponseToJson(
  AcademicReportBankSavedResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
