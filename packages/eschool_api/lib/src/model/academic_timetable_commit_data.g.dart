// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_timetable_commit_data.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicTimetableCommitData _$AcademicTimetableCommitDataFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicTimetableCommitData', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['lessons']);
  final val = AcademicTimetableCommitData(
    lessons: $checkedConvert(
      'lessons',
      (v) => (v as List<dynamic>)
          .map(
            (e) => AcademicTimetableLesson.fromJson(e as Map<String, dynamic>),
          )
          .toList(),
    ),
  );
  return val;
});

Map<String, dynamic> _$AcademicTimetableCommitDataToJson(
  AcademicTimetableCommitData instance,
) => <String, dynamic>{
  'lessons': instance.lessons.map((e) => e.toJson()).toList(),
};
