// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_report_family_card.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicReportFamilyCard _$AcademicReportFamilyCardFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'AcademicReportFamilyCard',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'batch_id',
        'session_year_id',
        'term_id',
        'year_name',
        'term_name',
        'learner_name',
        'provisional',
        'released_at',
        'read_at',
        'download_url',
      ],
    );
    final val = AcademicReportFamilyCard(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      batchId: $checkedConvert('batch_id', (v) => (v as num).toInt()),
      sessionYearId: $checkedConvert(
        'session_year_id',
        (v) => (v as num).toInt(),
      ),
      termId: $checkedConvert('term_id', (v) => (v as num?)?.toInt()),
      yearName: $checkedConvert('year_name', (v) => v as String),
      termName: $checkedConvert('term_name', (v) => v as String?),
      learnerName: $checkedConvert('learner_name', (v) => v as String),
      provisional: $checkedConvert('provisional', (v) => v as bool),
      releasedAt: $checkedConvert('released_at', (v) => v as String),
      readAt: $checkedConvert('read_at', (v) => v as String?),
      downloadUrl: $checkedConvert('download_url', (v) => v as String),
    );
    return val;
  },
  fieldKeyMap: const {
    'batchId': 'batch_id',
    'sessionYearId': 'session_year_id',
    'termId': 'term_id',
    'yearName': 'year_name',
    'termName': 'term_name',
    'learnerName': 'learner_name',
    'releasedAt': 'released_at',
    'readAt': 'read_at',
    'downloadUrl': 'download_url',
  },
);

Map<String, dynamic> _$AcademicReportFamilyCardToJson(
  AcademicReportFamilyCard instance,
) => <String, dynamic>{
  'id': instance.id,
  'batch_id': instance.batchId,
  'session_year_id': instance.sessionYearId,
  'term_id': instance.termId,
  'year_name': instance.yearName,
  'term_name': instance.termName,
  'learner_name': instance.learnerName,
  'provisional': instance.provisional,
  'released_at': instance.releasedAt,
  'read_at': instance.readAt,
  'download_url': instance.downloadUrl,
};
