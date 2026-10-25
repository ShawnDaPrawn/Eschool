// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'recognition_review.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

RecognitionReview _$RecognitionReviewFromJson(Map<String, dynamic> json) =>
    $checkedCreate('RecognitionReview', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['operation_id', 'revision', 'decision', 'reason'],
      );
      final val = RecognitionReview(
        operationId: $checkedConvert('operation_id', (v) => v as String),
        revision: $checkedConvert('revision', (v) => (v as num).toInt()),
        decision: $checkedConvert(
          'decision',
          (v) => $enumDecode(
            _$RecognitionReviewDecisionEnumEnumMap,
            v,
            unknownValue: RecognitionReviewDecisionEnum.unknownDefaultOpenApi,
          ),
        ),
        reason: $checkedConvert('reason', (v) => v as String),
      );
      return val;
    }, fieldKeyMap: const {'operationId': 'operation_id'});

Map<String, dynamic> _$RecognitionReviewToJson(RecognitionReview instance) =>
    <String, dynamic>{
      'operation_id': instance.operationId,
      'revision': instance.revision,
      'decision': _$RecognitionReviewDecisionEnumEnumMap[instance.decision]!,
      'reason': instance.reason,
    };

const _$RecognitionReviewDecisionEnumEnumMap = {
  RecognitionReviewDecisionEnum.approved: 'approved',
  RecognitionReviewDecisionEnum.rejected: 'rejected',
  RecognitionReviewDecisionEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
