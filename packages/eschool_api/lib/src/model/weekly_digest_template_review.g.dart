// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'weekly_digest_template_review.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WeeklyDigestTemplateReview _$WeeklyDigestTemplateReviewFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('WeeklyDigestTemplateReview', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['operation_id', 'revision', 'decision'],
  );
  final val = WeeklyDigestTemplateReview(
    operationId: $checkedConvert('operation_id', (v) => v as String),
    revision: $checkedConvert('revision', (v) => (v as num).toInt()),
    decision: $checkedConvert(
      'decision',
      (v) => $enumDecode(
        _$WeeklyDigestTemplateReviewDecisionEnumEnumMap,
        v,
        unknownValue:
            WeeklyDigestTemplateReviewDecisionEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
}, fieldKeyMap: const {'operationId': 'operation_id'});

Map<String, dynamic> _$WeeklyDigestTemplateReviewToJson(
  WeeklyDigestTemplateReview instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'revision': instance.revision,
  'decision':
      _$WeeklyDigestTemplateReviewDecisionEnumEnumMap[instance.decision]!,
};

const _$WeeklyDigestTemplateReviewDecisionEnumEnumMap = {
  WeeklyDigestTemplateReviewDecisionEnum.approve: 'approve',
  WeeklyDigestTemplateReviewDecisionEnum.revoke: 'revoke',
  WeeklyDigestTemplateReviewDecisionEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
