// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_report_attendance_day.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicReportAttendanceDay _$AcademicReportAttendanceDayFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicReportAttendanceDay', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['on_date', 'state', 'revision']);
  final val = AcademicReportAttendanceDay(
    onDate: $checkedConvert('on_date', (v) => v as String),
    state: $checkedConvert('state', (v) => v as String),
    revision: $checkedConvert('revision', (v) => (v as num?)?.toInt()),
  );
  return val;
}, fieldKeyMap: const {'onDate': 'on_date'});

Map<String, dynamic> _$AcademicReportAttendanceDayToJson(
  AcademicReportAttendanceDay instance,
) => <String, dynamic>{
  'on_date': instance.onDate,
  'state': instance.state,
  'revision': instance.revision,
};
