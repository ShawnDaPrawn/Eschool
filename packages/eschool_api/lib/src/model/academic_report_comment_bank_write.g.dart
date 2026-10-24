// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_report_comment_bank_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicReportCommentBankWrite _$AcademicReportCommentBankWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicReportCommentBankWrite', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['subject_id', 'scope', 'label', 'text', 'active'],
  );
  final val = AcademicReportCommentBankWrite(
    id: $checkedConvert('id', (v) => (v as num?)?.toInt()),
    revision: $checkedConvert('revision', (v) => (v as num?)?.toInt()),
    subjectId: $checkedConvert('subject_id', (v) => (v as num?)?.toInt()),
    scope: $checkedConvert('scope', (v) => v as String),
    label: $checkedConvert('label', (v) => v as String),
    text: $checkedConvert('text', (v) => v as String),
    active: $checkedConvert('active', (v) => v as bool),
  );
  return val;
}, fieldKeyMap: const {'subjectId': 'subject_id'});

Map<String, dynamic> _$AcademicReportCommentBankWriteToJson(
  AcademicReportCommentBankWrite instance,
) => <String, dynamic>{
  'id': ?instance.id,
  'revision': ?instance.revision,
  'subject_id': instance.subjectId,
  'scope': instance.scope,
  'label': instance.label,
  'text': instance.text,
  'active': instance.active,
};
