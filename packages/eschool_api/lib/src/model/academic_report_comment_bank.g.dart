// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_report_comment_bank.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicReportCommentBank _$AcademicReportCommentBankFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicReportCommentBank', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const [
      'id',
      'subject_id',
      'scope',
      'label',
      'text',
      'active',
      'revision',
    ],
  );
  final val = AcademicReportCommentBank(
    id: $checkedConvert('id', (v) => (v as num).toInt()),
    subjectId: $checkedConvert('subject_id', (v) => (v as num?)?.toInt()),
    scope: $checkedConvert('scope', (v) => v as String),
    label: $checkedConvert('label', (v) => v as String),
    text: $checkedConvert('text', (v) => v as String),
    active: $checkedConvert('active', (v) => v as bool),
    revision: $checkedConvert('revision', (v) => (v as num).toInt()),
  );
  return val;
}, fieldKeyMap: const {'subjectId': 'subject_id'});

Map<String, dynamic> _$AcademicReportCommentBankToJson(
  AcademicReportCommentBank instance,
) => <String, dynamic>{
  'id': instance.id,
  'subject_id': instance.subjectId,
  'scope': instance.scope,
  'label': instance.label,
  'text': instance.text,
  'active': instance.active,
  'revision': instance.revision,
};
