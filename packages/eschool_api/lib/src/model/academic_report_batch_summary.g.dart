// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_report_batch_summary.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicReportBatchSummary _$AcademicReportBatchSummaryFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'AcademicReportBatchSummary',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'state',
        'mode',
        'class_section_id',
        'term_id',
        'learner_count',
        'created_at',
        'released_at',
      ],
    );
    final val = AcademicReportBatchSummary(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      state: $checkedConvert('state', (v) => v as String),
      mode: $checkedConvert('mode', (v) => v as String),
      classSectionId: $checkedConvert(
        'class_section_id',
        (v) => (v as num).toInt(),
      ),
      termId: $checkedConvert('term_id', (v) => (v as num?)?.toInt()),
      learnerCount: $checkedConvert('learner_count', (v) => (v as num).toInt()),
      createdAt: $checkedConvert('created_at', (v) => v as String),
      releasedAt: $checkedConvert('released_at', (v) => v as String?),
    );
    return val;
  },
  fieldKeyMap: const {
    'classSectionId': 'class_section_id',
    'termId': 'term_id',
    'learnerCount': 'learner_count',
    'createdAt': 'created_at',
    'releasedAt': 'released_at',
  },
);

Map<String, dynamic> _$AcademicReportBatchSummaryToJson(
  AcademicReportBatchSummary instance,
) => <String, dynamic>{
  'id': instance.id,
  'state': instance.state,
  'mode': instance.mode,
  'class_section_id': instance.classSectionId,
  'term_id': instance.termId,
  'learner_count': instance.learnerCount,
  'created_at': instance.createdAt,
  'released_at': instance.releasedAt,
};
