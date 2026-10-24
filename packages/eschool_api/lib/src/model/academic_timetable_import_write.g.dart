// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_timetable_import_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicTimetableImportWrite _$AcademicTimetableImportWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicTimetableImportWrite', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['csv', 'session_year_id']);
  final val = AcademicTimetableImportWrite(
    csv: $checkedConvert('csv', (v) => v as String),
    sessionYearId: $checkedConvert(
      'session_year_id',
      (v) => (v as num).toInt(),
    ),
    defaults: $checkedConvert(
      'defaults',
      (v) => v == null
          ? null
          : AcademicTimetableImportDefaults.fromJson(v as Map<String, dynamic>),
    ),
    columns: $checkedConvert(
      'columns',
      (v) =>
          (v as Map<String, dynamic>?)?.map((k, e) => MapEntry(k, e as String)),
    ),
    values: $checkedConvert(
      'values',
      (v) => (v as Map<String, dynamic>?)?.map(
        (k, e) => MapEntry(
          k,
          (e as Map<String, dynamic>).map((k, e) => MapEntry(k, e as Object)),
        ),
      ),
    ),
  );
  return val;
}, fieldKeyMap: const {'sessionYearId': 'session_year_id'});

Map<String, dynamic> _$AcademicTimetableImportWriteToJson(
  AcademicTimetableImportWrite instance,
) => <String, dynamic>{
  'csv': instance.csv,
  'session_year_id': instance.sessionYearId,
  'defaults': ?instance.defaults?.toJson(),
  'columns': ?instance.columns,
  'values': ?instance.values,
};
