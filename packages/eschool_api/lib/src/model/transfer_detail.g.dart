// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'transfer_detail.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TransferDetail _$TransferDetailFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'TransferDetail',
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
        'consent_version',
        'reports',
        'documents',
        'released_marks',
        'attendance',
        'can_consent',
        'can_withdraw',
        'can_release',
        'can_cancel',
        'can_accept',
        'can_download',
      ],
    );
    final val = TransferDetail(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      publicId: $checkedConvert('public_id', (v) => v as String),
      state: $checkedConvert(
        'state',
        (v) => $enumDecode(
          _$TransferDetailStateEnumEnumMap,
          v,
          unknownValue: TransferDetailStateEnum.unknownDefaultOpenApi,
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
      consentVersion: $checkedConvert('consent_version', (v) => v as String),
      reports: $checkedConvert(
        'reports',
        (v) => (v as List<dynamic>)
            .map((e) => TransferReport.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
      documents: $checkedConvert(
        'documents',
        (v) => (v as List<dynamic>)
            .map((e) => TransferDocument.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
      releasedMarks: $checkedConvert(
        'released_marks',
        (v) => (v as List<dynamic>)
            .map((e) => TransferMark.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
      attendance: $checkedConvert(
        'attendance',
        (v) => (v as List<dynamic>)
            .map((e) => TransferAttendance.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
      canConsent: $checkedConvert('can_consent', (v) => v as bool),
      canWithdraw: $checkedConvert('can_withdraw', (v) => v as bool),
      canRelease: $checkedConvert('can_release', (v) => v as bool),
      canCancel: $checkedConvert('can_cancel', (v) => v as bool),
      canAccept: $checkedConvert('can_accept', (v) => v as bool),
      canDownload: $checkedConvert('can_download', (v) => v as bool),
      galleryChoice: $checkedConvert(
        'gallery_choice',
        (v) => v == null
            ? null
            : TransferGalleryChoice.fromJson(v as Map<String, dynamic>),
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
    'consentVersion': 'consent_version',
    'releasedMarks': 'released_marks',
    'canConsent': 'can_consent',
    'canWithdraw': 'can_withdraw',
    'canRelease': 'can_release',
    'canCancel': 'can_cancel',
    'canAccept': 'can_accept',
    'canDownload': 'can_download',
    'galleryChoice': 'gallery_choice',
  },
);

Map<String, dynamic> _$TransferDetailToJson(TransferDetail instance) =>
    <String, dynamic>{
      'id': instance.id,
      'public_id': instance.publicId,
      'state': _$TransferDetailStateEnumEnumMap[instance.state]!,
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
      'consent_version': instance.consentVersion,
      'reports': instance.reports.map((e) => e.toJson()).toList(),
      'documents': instance.documents.map((e) => e.toJson()).toList(),
      'released_marks': instance.releasedMarks.map((e) => e.toJson()).toList(),
      'attendance': instance.attendance.map((e) => e.toJson()).toList(),
      'can_consent': instance.canConsent,
      'can_withdraw': instance.canWithdraw,
      'can_release': instance.canRelease,
      'can_cancel': instance.canCancel,
      'can_accept': instance.canAccept,
      'can_download': instance.canDownload,
      'gallery_choice': ?instance.galleryChoice?.toJson(),
    };

const _$TransferDetailStateEnumEnumMap = {
  TransferDetailStateEnum.awaitingConsent: 'awaiting_consent',
  TransferDetailStateEnum.consented: 'consented',
  TransferDetailStateEnum.released: 'released',
  TransferDetailStateEnum.accepted: 'accepted',
  TransferDetailStateEnum.cancelled: 'cancelled',
  TransferDetailStateEnum.expired: 'expired',
  TransferDetailStateEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
