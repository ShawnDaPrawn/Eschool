// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_timetable_preview_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicTimetablePreviewWrite _$AcademicTimetablePreviewWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicTimetablePreviewWrite', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['rows']);
  final val = AcademicTimetablePreviewWrite(
    rows: $checkedConvert(
      'rows',
      (v) => (v as List<dynamic>)
          .map((e) => AcademicLessonWrite.fromJson(e as Map<String, dynamic>))
          .toList(),
    ),
  );
  return val;
});

Map<String, dynamic> _$AcademicTimetablePreviewWriteToJson(
  AcademicTimetablePreviewWrite instance,
) => <String, dynamic>{'rows': instance.rows.map((e) => e.toJson()).toList()};
