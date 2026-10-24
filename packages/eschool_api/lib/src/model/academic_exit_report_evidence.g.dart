// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_exit_report_evidence.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicExitReportEvidence _$AcademicExitReportEvidenceFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'AcademicExitReportEvidence',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'year_name',
        'term_name',
        'released_at',
        'sha256',
        'fingerprint',
      ],
    );
    final val = AcademicExitReportEvidence(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      yearName: $checkedConvert('year_name', (v) => v as String),
      termName: $checkedConvert('term_name', (v) => v as String?),
      releasedAt: $checkedConvert(
        'released_at',
        (v) => DateTime.parse(v as String),
      ),
      sha256: $checkedConvert('sha256', (v) => v as String?),
      fingerprint: $checkedConvert('fingerprint', (v) => v as String),
    );
    return val;
  },
  fieldKeyMap: const {
    'yearName': 'year_name',
    'termName': 'term_name',
    'releasedAt': 'released_at',
  },
);

Map<String, dynamic> _$AcademicExitReportEvidenceToJson(
  AcademicExitReportEvidence instance,
) => <String, dynamic>{
  'id': instance.id,
  'year_name': instance.yearName,
  'term_name': instance.termName,
  'released_at': instance.releasedAt.toIso8601String(),
  'sha256': instance.sha256,
  'fingerprint': instance.fingerprint,
};
