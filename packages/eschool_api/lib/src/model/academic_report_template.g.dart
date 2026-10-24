// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_report_template.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicReportTemplate _$AcademicReportTemplateFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicReportTemplate', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['id', 'name', 'revision', 'design']);
  final val = AcademicReportTemplate(
    id: $checkedConvert('id', (v) => (v as num).toInt()),
    name: $checkedConvert('name', (v) => v as String),
    revision: $checkedConvert('revision', (v) => (v as num).toInt()),
    design: $checkedConvert(
      'design',
      (v) => AcademicReportDesign.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$AcademicReportTemplateToJson(
  AcademicReportTemplate instance,
) => <String, dynamic>{
  'id': instance.id,
  'name': instance.name,
  'revision': instance.revision,
  'design': instance.design.toJson(),
};
