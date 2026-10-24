// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_timetable_preview_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicTimetablePreviewResponse _$AcademicTimetablePreviewResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicTimetablePreviewResponse', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = AcademicTimetablePreviewResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => AcademicTimetablePreviewData.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$AcademicTimetablePreviewResponseToJson(
  AcademicTimetablePreviewResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
