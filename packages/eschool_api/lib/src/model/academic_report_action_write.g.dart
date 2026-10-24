// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_report_action_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicReportActionWrite _$AcademicReportActionWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicReportActionWrite', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['revision', 'fingerprint']);
  final val = AcademicReportActionWrite(
    revision: $checkedConvert('revision', (v) => (v as num).toInt()),
    fingerprint: $checkedConvert('fingerprint', (v) => v as String),
  );
  return val;
});

Map<String, dynamic> _$AcademicReportActionWriteToJson(
  AcademicReportActionWrite instance,
) => <String, dynamic>{
  'revision': instance.revision,
  'fingerprint': instance.fingerprint,
};
