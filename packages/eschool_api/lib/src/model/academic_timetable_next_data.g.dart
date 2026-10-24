// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_timetable_next_data.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicTimetableNextData _$AcademicTimetableNextDataFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicTimetableNextData', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['lesson']);
  final val = AcademicTimetableNextData(
    lesson: $checkedConvert(
      'lesson',
      (v) => v == null
          ? null
          : AcademicTimetableLesson.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$AcademicTimetableNextDataToJson(
  AcademicTimetableNextData instance,
) => <String, dynamic>{'lesson': instance.lesson?.toJson()};
