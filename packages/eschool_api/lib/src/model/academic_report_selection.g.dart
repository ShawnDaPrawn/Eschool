// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_report_selection.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicReportSelection _$AcademicReportSelectionFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'AcademicReportSelection',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'template_id',
        'session_year_id',
        'term_id',
        'class_section_id',
        'learner_ids',
        'mode',
      ],
    );
    final val = AcademicReportSelection(
      templateId: $checkedConvert('template_id', (v) => (v as num).toInt()),
      sessionYearId: $checkedConvert(
        'session_year_id',
        (v) => (v as num).toInt(),
      ),
      termId: $checkedConvert('term_id', (v) => (v as num?)?.toInt()),
      classSectionId: $checkedConvert(
        'class_section_id',
        (v) => (v as num).toInt(),
      ),
      learnerIds: $checkedConvert(
        'learner_ids',
        (v) => (v as List<dynamic>).map((e) => (e as num).toInt()).toList(),
      ),
      mode: $checkedConvert('mode', (v) => v as String),
    );
    return val;
  },
  fieldKeyMap: const {
    'templateId': 'template_id',
    'sessionYearId': 'session_year_id',
    'termId': 'term_id',
    'classSectionId': 'class_section_id',
    'learnerIds': 'learner_ids',
  },
);

Map<String, dynamic> _$AcademicReportSelectionToJson(
  AcademicReportSelection instance,
) => <String, dynamic>{
  'template_id': instance.templateId,
  'session_year_id': instance.sessionYearId,
  'term_id': instance.termId,
  'class_section_id': instance.classSectionId,
  'learner_ids': instance.learnerIds,
  'mode': instance.mode,
};
