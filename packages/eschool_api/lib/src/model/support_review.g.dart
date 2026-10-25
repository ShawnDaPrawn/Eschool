// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'support_review.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SupportReview _$SupportReviewFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'SupportReview',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'id',
            'author_id',
            'author_name',
            'reviewed_on',
            'next_review_on',
            'kind',
            'outcome',
          ],
        );
        final val = SupportReview(
          id: $checkedConvert('id', (v) => (v as num).toInt()),
          authorId: $checkedConvert('author_id', (v) => (v as num).toInt()),
          authorName: $checkedConvert('author_name', (v) => v as String),
          reviewedOn: $checkedConvert('reviewed_on', (v) => v as String),
          nextReviewOn: $checkedConvert('next_review_on', (v) => v as String?),
          kind: $checkedConvert(
            'kind',
            (v) => $enumDecode(
              _$SupportReviewKindEnumEnumMap,
              v,
              unknownValue: SupportReviewKindEnum.unknownDefaultOpenApi,
            ),
          ),
          outcome: $checkedConvert('outcome', (v) => v as String),
        );
        return val;
      },
      fieldKeyMap: const {
        'authorId': 'author_id',
        'authorName': 'author_name',
        'reviewedOn': 'reviewed_on',
        'nextReviewOn': 'next_review_on',
      },
    );

Map<String, dynamic> _$SupportReviewToJson(SupportReview instance) =>
    <String, dynamic>{
      'id': instance.id,
      'author_id': instance.authorId,
      'author_name': instance.authorName,
      'reviewed_on': instance.reviewedOn,
      'next_review_on': instance.nextReviewOn,
      'kind': _$SupportReviewKindEnumEnumMap[instance.kind]!,
      'outcome': instance.outcome,
    };

const _$SupportReviewKindEnumEnumMap = {
  SupportReviewKindEnum.review: 'review',
  SupportReviewKindEnum.closure: 'closure',
  SupportReviewKindEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
