// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_report_comment_row.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicReportCommentRow _$AcademicReportCommentRowFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'AcademicReportCommentRow',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'card_id',
        'batch_id',
        'batch_revision',
        'learner_id',
        'learner_name',
        'period_name',
        'subject_id',
        'scope_label',
        'revision',
        'text',
      ],
    );
    final val = AcademicReportCommentRow(
      cardId: $checkedConvert('card_id', (v) => (v as num).toInt()),
      batchId: $checkedConvert('batch_id', (v) => (v as num).toInt()),
      batchRevision: $checkedConvert(
        'batch_revision',
        (v) => (v as num).toInt(),
      ),
      learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
      learnerName: $checkedConvert('learner_name', (v) => v as String),
      periodName: $checkedConvert('period_name', (v) => v as String),
      subjectId: $checkedConvert('subject_id', (v) => (v as num?)?.toInt()),
      scopeLabel: $checkedConvert('scope_label', (v) => v as String),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      text: $checkedConvert('text', (v) => v as String),
    );
    return val;
  },
  fieldKeyMap: const {
    'cardId': 'card_id',
    'batchId': 'batch_id',
    'batchRevision': 'batch_revision',
    'learnerId': 'learner_id',
    'learnerName': 'learner_name',
    'periodName': 'period_name',
    'subjectId': 'subject_id',
    'scopeLabel': 'scope_label',
  },
);

Map<String, dynamic> _$AcademicReportCommentRowToJson(
  AcademicReportCommentRow instance,
) => <String, dynamic>{
  'card_id': instance.cardId,
  'batch_id': instance.batchId,
  'batch_revision': instance.batchRevision,
  'learner_id': instance.learnerId,
  'learner_name': instance.learnerName,
  'period_name': instance.periodName,
  'subject_id': instance.subjectId,
  'scope_label': instance.scopeLabel,
  'revision': instance.revision,
  'text': instance.text,
};
