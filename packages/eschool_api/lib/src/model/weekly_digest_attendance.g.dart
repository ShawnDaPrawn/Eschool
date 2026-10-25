// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'weekly_digest_attendance.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WeeklyDigestAttendance _$WeeklyDigestAttendanceFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('WeeklyDigestAttendance', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['recorded', 'present', 'absent', 'excused'],
  );
  final val = WeeklyDigestAttendance(
    recorded: $checkedConvert('recorded', (v) => (v as num).toInt()),
    present: $checkedConvert('present', (v) => (v as num).toInt()),
    absent: $checkedConvert('absent', (v) => (v as num).toInt()),
    excused: $checkedConvert('excused', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$WeeklyDigestAttendanceToJson(
  WeeklyDigestAttendance instance,
) => <String, dynamic>{
  'recorded': instance.recorded,
  'present': instance.present,
  'absent': instance.absent,
  'excused': instance.excused,
};
