// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_report_comment_data.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicReportCommentData _$AcademicReportCommentDataFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicReportCommentData', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const [
      'id',
      'revision',
      'batch_revision',
      'fingerprint',
      'text',
    ],
  );
  final val = AcademicReportCommentData(
    id: $checkedConvert('id', (v) => (v as num).toInt()),
    revision: $checkedConvert('revision', (v) => (v as num).toInt()),
    batchRevision: $checkedConvert('batch_revision', (v) => (v as num).toInt()),
    fingerprint: $checkedConvert('fingerprint', (v) => v as String),
    text: $checkedConvert('text', (v) => v as String),
  );
  return val;
}, fieldKeyMap: const {'batchRevision': 'batch_revision'});

Map<String, dynamic> _$AcademicReportCommentDataToJson(
  AcademicReportCommentData instance,
) => <String, dynamic>{
  'id': instance.id,
  'revision': instance.revision,
  'batch_revision': instance.batchRevision,
  'fingerprint': instance.fingerprint,
  'text': instance.text,
};
