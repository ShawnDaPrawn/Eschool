// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_report_saved_revision.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicReportSavedRevision _$AcademicReportSavedRevisionFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicReportSavedRevision', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['id', 'revision']);
  final val = AcademicReportSavedRevision(
    id: $checkedConvert('id', (v) => (v as num).toInt()),
    revision: $checkedConvert('revision', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$AcademicReportSavedRevisionToJson(
  AcademicReportSavedRevision instance,
) => <String, dynamic>{'id': instance.id, 'revision': instance.revision};
