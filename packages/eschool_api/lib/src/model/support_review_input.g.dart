// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'support_review_input.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SupportReviewInput _$SupportReviewInputFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'SupportReviewInput',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'operation_id',
            'revision',
            'reviewed_on',
            'outcome',
          ],
        );
        final val = SupportReviewInput(
          operationId: $checkedConvert('operation_id', (v) => v as String),
          revision: $checkedConvert('revision', (v) => (v as num).toInt()),
          reviewedOn: $checkedConvert('reviewed_on', (v) => v as String),
          nextReviewOn: $checkedConvert('next_review_on', (v) => v as String?),
          outcome: $checkedConvert('outcome', (v) => v as String),
        );
        return val;
      },
      fieldKeyMap: const {
        'operationId': 'operation_id',
        'reviewedOn': 'reviewed_on',
        'nextReviewOn': 'next_review_on',
      },
    );

Map<String, dynamic> _$SupportReviewInputToJson(SupportReviewInput instance) =>
    <String, dynamic>{
      'operation_id': instance.operationId,
      'revision': instance.revision,
      'reviewed_on': instance.reviewedOn,
      'next_review_on': ?instance.nextReviewOn,
      'outcome': instance.outcome,
    };
