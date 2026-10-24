// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_report_subject_result.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicReportSubjectResult _$AcademicReportSubjectResultFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'AcademicReportSubjectResult',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'subject_id',
        'subject_name',
        'snapshot_id',
        'fingerprint',
        'percentage_bp',
        'rating_level',
        'coverage_bp',
        'complete',
        'ready',
        'result_status',
      ],
    );
    final val = AcademicReportSubjectResult(
      subjectId: $checkedConvert('subject_id', (v) => (v as num).toInt()),
      subjectName: $checkedConvert('subject_name', (v) => v as String),
      snapshotId: $checkedConvert('snapshot_id', (v) => (v as num?)?.toInt()),
      fingerprint: $checkedConvert('fingerprint', (v) => v as String?),
      percentageBp: $checkedConvert(
        'percentage_bp',
        (v) => (v as num?)?.toInt(),
      ),
      ratingLevel: $checkedConvert('rating_level', (v) => (v as num?)?.toInt()),
      coverageBp: $checkedConvert('coverage_bp', (v) => (v as num).toInt()),
      complete: $checkedConvert('complete', (v) => v as bool),
      ready: $checkedConvert('ready', (v) => v as bool),
      resultStatus: $checkedConvert('result_status', (v) => v as String),
    );
    return val;
  },
  fieldKeyMap: const {
    'subjectId': 'subject_id',
    'subjectName': 'subject_name',
    'snapshotId': 'snapshot_id',
    'percentageBp': 'percentage_bp',
    'ratingLevel': 'rating_level',
    'coverageBp': 'coverage_bp',
    'resultStatus': 'result_status',
  },
);

Map<String, dynamic> _$AcademicReportSubjectResultToJson(
  AcademicReportSubjectResult instance,
) => <String, dynamic>{
  'subject_id': instance.subjectId,
  'subject_name': instance.subjectName,
  'snapshot_id': instance.snapshotId,
  'fingerprint': instance.fingerprint,
  'percentage_bp': instance.percentageBp,
  'rating_level': instance.ratingLevel,
  'coverage_bp': instance.coverageBp,
  'complete': instance.complete,
  'ready': instance.ready,
  'result_status': instance.resultStatus,
};
