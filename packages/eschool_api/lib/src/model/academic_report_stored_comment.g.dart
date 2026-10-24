// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_report_stored_comment.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicReportStoredComment _$AcademicReportStoredCommentFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicReportStoredComment', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['scope_key', 'text', 'revision']);
  final val = AcademicReportStoredComment(
    scopeKey: $checkedConvert('scope_key', (v) => v as String),
    text: $checkedConvert('text', (v) => v as String),
    revision: $checkedConvert('revision', (v) => (v as num).toInt()),
  );
  return val;
}, fieldKeyMap: const {'scopeKey': 'scope_key'});

Map<String, dynamic> _$AcademicReportStoredCommentToJson(
  AcademicReportStoredComment instance,
) => <String, dynamic>{
  'scope_key': instance.scopeKey,
  'text': instance.text,
  'revision': instance.revision,
};
