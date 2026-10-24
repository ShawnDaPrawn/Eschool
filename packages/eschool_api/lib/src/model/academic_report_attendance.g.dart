// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_report_attendance.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicReportAttendance _$AcademicReportAttendanceFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'AcademicReportAttendance',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'days_recorded',
        'present_half_days',
        'absent_half_days',
        'late_days',
        'excused_days',
        'evidence',
      ],
    );
    final val = AcademicReportAttendance(
      daysRecorded: $checkedConvert('days_recorded', (v) => (v as num).toInt()),
      presentHalfDays: $checkedConvert(
        'present_half_days',
        (v) => (v as num).toInt(),
      ),
      absentHalfDays: $checkedConvert(
        'absent_half_days',
        (v) => (v as num).toInt(),
      ),
      lateDays: $checkedConvert('late_days', (v) => (v as num).toInt()),
      excusedDays: $checkedConvert('excused_days', (v) => (v as num).toInt()),
      evidence: $checkedConvert(
        'evidence',
        (v) => (v as List<dynamic>)
            .map(
              (e) => AcademicReportAttendanceDay.fromJson(
                e as Map<String, dynamic>,
              ),
            )
            .toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'daysRecorded': 'days_recorded',
    'presentHalfDays': 'present_half_days',
    'absentHalfDays': 'absent_half_days',
    'lateDays': 'late_days',
    'excusedDays': 'excused_days',
  },
);

Map<String, dynamic> _$AcademicReportAttendanceToJson(
  AcademicReportAttendance instance,
) => <String, dynamic>{
  'days_recorded': instance.daysRecorded,
  'present_half_days': instance.presentHalfDays,
  'absent_half_days': instance.absentHalfDays,
  'late_days': instance.lateDays,
  'excused_days': instance.excusedDays,
  'evidence': instance.evidence.map((e) => e.toJson()).toList(),
};
