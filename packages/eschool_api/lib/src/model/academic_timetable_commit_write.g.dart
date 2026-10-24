// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_timetable_commit_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicTimetableCommitWrite _$AcademicTimetableCommitWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicTimetableCommitWrite', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['rows', 'fingerprint']);
  final val = AcademicTimetableCommitWrite(
    rows: $checkedConvert(
      'rows',
      (v) => (v as List<dynamic>)
          .map((e) => AcademicLessonWrite.fromJson(e as Map<String, dynamic>))
          .toList(),
    ),
    fingerprint: $checkedConvert('fingerprint', (v) => v as String),
  );
  return val;
});

Map<String, dynamic> _$AcademicTimetableCommitWriteToJson(
  AcademicTimetableCommitWrite instance,
) => <String, dynamic>{
  'rows': instance.rows.map((e) => e.toJson()).toList(),
  'fingerprint': instance.fingerprint,
};
