// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_exit_preview.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicExitPreview _$AcademicExitPreviewFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'AcademicExitPreview',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'selection',
        'learner_id',
        'learner_name',
        'last_attended_on',
        'attendance_through',
        'reports',
        'documents',
        'released_mark_count',
        'attendance_count',
        'enrolment_count',
        'fingerprint',
        'can_prepare',
        'issues',
        'estimated_bytes',
        'retention_days',
      ],
    );
    final val = AcademicExitPreview(
      selection: $checkedConvert(
        'selection',
        (v) => AcademicExitSelection.fromJson(v as Map<String, dynamic>),
      ),
      learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
      learnerName: $checkedConvert('learner_name', (v) => v as String),
      lastAttendedOn: $checkedConvert('last_attended_on', (v) => v as String),
      attendanceThrough: $checkedConvert(
        'attendance_through',
        (v) => v as String,
      ),
      reports: $checkedConvert(
        'reports',
        (v) => (v as List<dynamic>)
            .map(
              (e) => AcademicExitReportEvidence.fromJson(
                e as Map<String, dynamic>,
              ),
            )
            .toList(),
      ),
      documents: $checkedConvert(
        'documents',
        (v) => (v as List<dynamic>)
            .map(
              (e) => AcademicExitDocumentEvidence.fromJson(
                e as Map<String, dynamic>,
              ),
            )
            .toList(),
      ),
      releasedMarkCount: $checkedConvert(
        'released_mark_count',
        (v) => (v as num).toInt(),
      ),
      attendanceCount: $checkedConvert(
        'attendance_count',
        (v) => (v as num).toInt(),
      ),
      enrolmentCount: $checkedConvert(
        'enrolment_count',
        (v) => (v as num).toInt(),
      ),
      fingerprint: $checkedConvert('fingerprint', (v) => v as String),
      canPrepare: $checkedConvert('can_prepare', (v) => v as bool),
      issues: $checkedConvert(
        'issues',
        (v) => (v as List<dynamic>)
            .map((e) => AcademicExitIssue.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
      estimatedBytes: $checkedConvert(
        'estimated_bytes',
        (v) => (v as num).toInt(),
      ),
      retentionDays: $checkedConvert(
        'retention_days',
        (v) => (v as num).toInt(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'learnerId': 'learner_id',
    'learnerName': 'learner_name',
    'lastAttendedOn': 'last_attended_on',
    'attendanceThrough': 'attendance_through',
    'releasedMarkCount': 'released_mark_count',
    'attendanceCount': 'attendance_count',
    'enrolmentCount': 'enrolment_count',
    'canPrepare': 'can_prepare',
    'estimatedBytes': 'estimated_bytes',
    'retentionDays': 'retention_days',
  },
);

Map<String, dynamic> _$AcademicExitPreviewToJson(
  AcademicExitPreview instance,
) => <String, dynamic>{
  'selection': instance.selection.toJson(),
  'learner_id': instance.learnerId,
  'learner_name': instance.learnerName,
  'last_attended_on': instance.lastAttendedOn,
  'attendance_through': instance.attendanceThrough,
  'reports': instance.reports.map((e) => e.toJson()).toList(),
  'documents': instance.documents.map((e) => e.toJson()).toList(),
  'released_mark_count': instance.releasedMarkCount,
  'attendance_count': instance.attendanceCount,
  'enrolment_count': instance.enrolmentCount,
  'fingerprint': instance.fingerprint,
  'can_prepare': instance.canPrepare,
  'issues': instance.issues.map((e) => e.toJson()).toList(),
  'estimated_bytes': instance.estimatedBytes,
  'retention_days': instance.retentionDays,
};
