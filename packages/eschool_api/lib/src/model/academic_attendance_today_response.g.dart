// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_attendance_today_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicAttendanceTodayResponse _$AcademicAttendanceTodayResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicAttendanceTodayResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = AcademicAttendanceTodayResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => AcademicAttendanceTodayData.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$AcademicAttendanceTodayResponseToJson(
  AcademicAttendanceTodayResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
