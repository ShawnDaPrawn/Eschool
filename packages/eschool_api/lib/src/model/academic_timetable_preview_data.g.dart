// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_timetable_preview_data.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicTimetablePreviewData _$AcademicTimetablePreviewDataFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicTimetablePreviewData', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['rows', 'conflicts', 'can_save', 'fingerprint'],
  );
  final val = AcademicTimetablePreviewData(
    rows: $checkedConvert(
      'rows',
      (v) => (v as List<dynamic>)
          .map((e) => AcademicLessonWrite.fromJson(e as Map<String, dynamic>))
          .toList(),
    ),
    conflicts: $checkedConvert(
      'conflicts',
      (v) => (v as List<dynamic>)
          .map(
            (e) => AcademicTimetableClash.fromJson(e as Map<String, dynamic>),
          )
          .toList(),
    ),
    canSave: $checkedConvert('can_save', (v) => v as bool),
    fingerprint: $checkedConvert('fingerprint', (v) => v as String),
    importedRows: $checkedConvert('imported_rows', (v) => (v as num?)?.toInt()),
  );
  return val;
}, fieldKeyMap: const {'canSave': 'can_save', 'importedRows': 'imported_rows'});

Map<String, dynamic> _$AcademicTimetablePreviewDataToJson(
  AcademicTimetablePreviewData instance,
) => <String, dynamic>{
  'rows': instance.rows.map((e) => e.toJson()).toList(),
  'conflicts': instance.conflicts.map((e) => e.toJson()).toList(),
  'can_save': instance.canSave,
  'fingerprint': instance.fingerprint,
  'imported_rows': ?instance.importedRows,
};
