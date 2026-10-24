// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_report_template_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicReportTemplateWrite _$AcademicReportTemplateWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicReportTemplateWrite', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['name', 'design']);
  final val = AcademicReportTemplateWrite(
    id: $checkedConvert('id', (v) => (v as num?)?.toInt()),
    revision: $checkedConvert('revision', (v) => (v as num?)?.toInt()),
    name: $checkedConvert('name', (v) => v as String),
    design: $checkedConvert(
      'design',
      (v) => AcademicReportDesign.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$AcademicReportTemplateWriteToJson(
  AcademicReportTemplateWrite instance,
) => <String, dynamic>{
  'id': ?instance.id,
  'revision': ?instance.revision,
  'name': instance.name,
  'design': instance.design.toJson(),
};
