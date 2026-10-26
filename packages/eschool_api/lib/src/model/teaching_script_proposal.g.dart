// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'teaching_script_proposal.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TeachingScriptProposal _$TeachingScriptProposalFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'TeachingScriptProposal',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const ['question_id', 'mark', 'confidence_bp', 'reason'],
    );
    final val = TeachingScriptProposal(
      questionId: $checkedConvert('question_id', (v) => (v as num).toInt()),
      mark: $checkedConvert('mark', (v) => v as String?),
      confidenceBp: $checkedConvert('confidence_bp', (v) => (v as num).toInt()),
      reason: $checkedConvert('reason', (v) => v as String),
    );
    return val;
  },
  fieldKeyMap: const {
    'questionId': 'question_id',
    'confidenceBp': 'confidence_bp',
  },
);

Map<String, dynamic> _$TeachingScriptProposalToJson(
  TeachingScriptProposal instance,
) => <String, dynamic>{
  'question_id': instance.questionId,
  'mark': instance.mark,
  'confidence_bp': instance.confidenceBp,
  'reason': instance.reason,
};
