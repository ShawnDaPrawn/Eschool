// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_report_comment_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicReportCommentWrite _$AcademicReportCommentWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'AcademicReportCommentWrite',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const ['subject_id', 'text', 'revision', 'batch_revision'],
    );
    final val = AcademicReportCommentWrite(
      subjectId: $checkedConvert('subject_id', (v) => (v as num?)?.toInt()),
      text: $checkedConvert('text', (v) => v as String?),
      bankId: $checkedConvert('bank_id', (v) => (v as num?)?.toInt()),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      batchRevision: $checkedConvert(
        'batch_revision',
        (v) => (v as num).toInt(),
      ),
      reason: $checkedConvert('reason', (v) => v as String?),
    );
    return val;
  },
  fieldKeyMap: const {
    'subjectId': 'subject_id',
    'bankId': 'bank_id',
    'batchRevision': 'batch_revision',
  },
);

Map<String, dynamic> _$AcademicReportCommentWriteToJson(
  AcademicReportCommentWrite instance,
) => <String, dynamic>{
  'subject_id': instance.subjectId,
  'text': instance.text,
  'bank_id': ?instance.bankId,
  'revision': instance.revision,
  'batch_revision': instance.batchRevision,
  'reason': ?instance.reason,
};
