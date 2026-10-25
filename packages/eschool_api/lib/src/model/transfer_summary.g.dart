// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'transfer_summary.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TransferSummary _$TransferSummaryFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'TransferSummary',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'public_id',
        'state',
        'revision',
        'fingerprint',
        'learner_name',
        'student_number',
        'guardian_name',
        'from_school_name',
        'to_school_name',
        'last_attended_on',
        'starts_on',
        'expires_at',
        'export_only',
        'report_count',
        'document_count',
        'mark_count',
        'attendance_count',
      ],
    );
    final val = TransferSummary(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      publicId: $checkedConvert('public_id', (v) => v as String),
      state: $checkedConvert(
        'state',
        (v) => $enumDecode(
          _$TransferSummaryStateEnumEnumMap,
          v,
          unknownValue: TransferSummaryStateEnum.unknownDefaultOpenApi,
        ),
      ),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      fingerprint: $checkedConvert('fingerprint', (v) => v as String),
      learnerName: $checkedConvert('learner_name', (v) => v as String),
      studentNumber: $checkedConvert('student_number', (v) => v as String?),
      guardianName: $checkedConvert('guardian_name', (v) => v as String),
      fromSchoolName: $checkedConvert('from_school_name', (v) => v as String),
      toSchoolName: $checkedConvert('to_school_name', (v) => v as String),
      lastAttendedOn: $checkedConvert('last_attended_on', (v) => v as String),
      startsOn: $checkedConvert('starts_on', (v) => v as String),
      expiresAt: $checkedConvert(
        'expires_at',
        (v) => v == null ? null : DateTime.parse(v as String),
      ),
      exportOnly: $checkedConvert('export_only', (v) => v as bool),
      reportCount: $checkedConvert('report_count', (v) => (v as num).toInt()),
      documentCount: $checkedConvert(
        'document_count',
        (v) => (v as num).toInt(),
      ),
      markCount: $checkedConvert('mark_count', (v) => (v as num).toInt()),
      attendanceCount: $checkedConvert(
        'attendance_count',
        (v) => (v as num).toInt(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'publicId': 'public_id',
    'learnerName': 'learner_name',
    'studentNumber': 'student_number',
    'guardianName': 'guardian_name',
    'fromSchoolName': 'from_school_name',
    'toSchoolName': 'to_school_name',
    'lastAttendedOn': 'last_attended_on',
    'startsOn': 'starts_on',
    'expiresAt': 'expires_at',
    'exportOnly': 'export_only',
    'reportCount': 'report_count',
    'documentCount': 'document_count',
    'markCount': 'mark_count',
    'attendanceCount': 'attendance_count',
  },
);

Map<String, dynamic> _$TransferSummaryToJson(TransferSummary instance) =>
    <String, dynamic>{
      'id': instance.id,
      'public_id': instance.publicId,
      'state': _$TransferSummaryStateEnumEnumMap[instance.state]!,
      'revision': instance.revision,
      'fingerprint': instance.fingerprint,
      'learner_name': instance.learnerName,
      'student_number': instance.studentNumber,
      'guardian_name': instance.guardianName,
      'from_school_name': instance.fromSchoolName,
      'to_school_name': instance.toSchoolName,
      'last_attended_on': instance.lastAttendedOn,
      'starts_on': instance.startsOn,
      'expires_at': instance.expiresAt?.toIso8601String(),
      'export_only': instance.exportOnly,
      'report_count': instance.reportCount,
      'document_count': instance.documentCount,
      'mark_count': instance.markCount,
      'attendance_count': instance.attendanceCount,
    };

const _$TransferSummaryStateEnumEnumMap = {
  TransferSummaryStateEnum.awaitingConsent: 'awaiting_consent',
  TransferSummaryStateEnum.consented: 'consented',
  TransferSummaryStateEnum.released: 'released',
  TransferSummaryStateEnum.accepted: 'accepted',
  TransferSummaryStateEnum.cancelled: 'cancelled',
  TransferSummaryStateEnum.expired: 'expired',
  TransferSummaryStateEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
