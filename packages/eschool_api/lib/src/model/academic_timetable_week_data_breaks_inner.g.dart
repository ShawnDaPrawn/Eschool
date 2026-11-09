// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_timetable_week_data_breaks_inner.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicTimetableWeekDataBreaksInner
_$AcademicTimetableWeekDataBreaksInnerFromJson(Map<String, dynamic> json) =>
    $checkedCreate('AcademicTimetableWeekDataBreaksInner', json, (
      $checkedConvert,
    ) {
      $checkKeys(
        json,
        requiredKeys: const ['date', 'name', 'starts_at', 'ends_at'],
      );
      final val = AcademicTimetableWeekDataBreaksInner(
        date: $checkedConvert('date', (v) => v as String),
        name: $checkedConvert('name', (v) => v as String),
        startsAt: $checkedConvert('starts_at', (v) => v as String),
        endsAt: $checkedConvert('ends_at', (v) => v as String),
      );
      return val;
    }, fieldKeyMap: const {'startsAt': 'starts_at', 'endsAt': 'ends_at'});

Map<String, dynamic> _$AcademicTimetableWeekDataBreaksInnerToJson(
  AcademicTimetableWeekDataBreaksInner instance,
) => <String, dynamic>{
  'date': instance.date,
  'name': instance.name,
  'starts_at': instance.startsAt,
  'ends_at': instance.endsAt,
};
