// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_timetable_week_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicTimetableWeekResponse _$AcademicTimetableWeekResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicTimetableWeekResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = AcademicTimetableWeekResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => AcademicTimetableWeekData.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$AcademicTimetableWeekResponseToJson(
  AcademicTimetableWeekResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
