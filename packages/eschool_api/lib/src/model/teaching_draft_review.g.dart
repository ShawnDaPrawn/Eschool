// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'teaching_draft_review.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TeachingDraftReview _$TeachingDraftReviewFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'TeachingDraftReview',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'operation_id',
            'expected_revision',
            'action',
            'definition',
            'confirmed',
          ],
        );
        final val = TeachingDraftReview(
          operationId: $checkedConvert('operation_id', (v) => v as String),
          expectedRevision: $checkedConvert(
            'expected_revision',
            (v) => (v as num).toInt(),
          ),
          action: $checkedConvert(
            'action',
            (v) => $enumDecode(
              _$TeachingDraftReviewActionEnumEnumMap,
              v,
              unknownValue: TeachingDraftReviewActionEnum.unknownDefaultOpenApi,
            ),
          ),
          definition: $checkedConvert(
            'definition',
            (v) => v == null
                ? null
                : TeachingDraftDefinition.fromJson(v as Map<String, dynamic>),
          ),
          confirmed: $checkedConvert('confirmed', (v) => v as bool),
        );
        return val;
      },
      fieldKeyMap: const {
        'operationId': 'operation_id',
        'expectedRevision': 'expected_revision',
      },
    );

Map<String, dynamic> _$TeachingDraftReviewToJson(
  TeachingDraftReview instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'expected_revision': instance.expectedRevision,
  'action': _$TeachingDraftReviewActionEnumEnumMap[instance.action]!,
  'definition': instance.definition?.toJson(),
  'confirmed': instance.confirmed,
};

const _$TeachingDraftReviewActionEnumEnumMap = {
  TeachingDraftReviewActionEnum.edit: 'edit',
  TeachingDraftReviewActionEnum.approve: 'approve',
  TeachingDraftReviewActionEnum.discard: 'discard',
  TeachingDraftReviewActionEnum.publishPractice: 'publish_practice',
  TeachingDraftReviewActionEnum.confirmMarks: 'confirm_marks',
  TeachingDraftReviewActionEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
