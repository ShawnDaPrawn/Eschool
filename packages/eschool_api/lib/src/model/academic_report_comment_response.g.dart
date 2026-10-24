// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_report_comment_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicReportCommentResponse _$AcademicReportCommentResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicReportCommentResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = AcademicReportCommentResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => AcademicReportCommentData.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$AcademicReportCommentResponseToJson(
  AcademicReportCommentResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
