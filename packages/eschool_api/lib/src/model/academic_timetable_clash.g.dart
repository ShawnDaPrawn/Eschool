// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_timetable_clash.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicTimetableClash _$AcademicTimetableClashFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicTimetableClash', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['row', 'lesson_id', 'date', 'reasons']);
  final val = AcademicTimetableClash(
    row: $checkedConvert('row', (v) => (v as num).toInt()),
    lessonId: $checkedConvert('lesson_id', (v) => (v as num?)?.toInt()),
    date: $checkedConvert('date', (v) => v as String?),
    reasons: $checkedConvert(
      'reasons',
      (v) => (v as List<dynamic>).map((e) => e as String).toList(),
    ),
  );
  return val;
}, fieldKeyMap: const {'lessonId': 'lesson_id'});

Map<String, dynamic> _$AcademicTimetableClashToJson(
  AcademicTimetableClash instance,
) => <String, dynamic>{
  'row': instance.row,
  'lesson_id': instance.lessonId,
  'date': instance.date,
  'reasons': instance.reasons,
};
