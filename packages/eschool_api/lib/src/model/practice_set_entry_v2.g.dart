// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'practice_set_entry_v2.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PracticeSetEntryV2 _$PracticeSetEntryV2FromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'PracticeSetEntryV2',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'id',
            'item_id',
            'topic_id',
            'source_version_id',
            'estimated_seconds',
            'question',
            'review',
          ],
        );
        final val = PracticeSetEntryV2(
          id: $checkedConvert('id', (v) => (v as num).toInt()),
          itemId: $checkedConvert('item_id', (v) => (v as num).toInt()),
          topicId: $checkedConvert('topic_id', (v) => (v as num).toInt()),
          sourceVersionId: $checkedConvert(
            'source_version_id',
            (v) => (v as num).toInt(),
          ),
          estimatedSeconds: $checkedConvert(
            'estimated_seconds',
            (v) => (v as num).toInt(),
          ),
          question: $checkedConvert(
            'question',
            (v) => PracticeSetQuestion.fromJson(v as Map<String, dynamic>),
          ),
          review: $checkedConvert(
            'review',
            (v) => v == null
                ? null
                : PracticeReviewV2.fromJson(v as Map<String, dynamic>),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'itemId': 'item_id',
        'topicId': 'topic_id',
        'sourceVersionId': 'source_version_id',
        'estimatedSeconds': 'estimated_seconds',
      },
    );

Map<String, dynamic> _$PracticeSetEntryV2ToJson(PracticeSetEntryV2 instance) =>
    <String, dynamic>{
      'id': instance.id,
      'item_id': instance.itemId,
      'topic_id': instance.topicId,
      'source_version_id': instance.sourceVersionId,
      'estimated_seconds': instance.estimatedSeconds,
      'question': instance.question.toJson(),
      'review': instance.review?.toJson(),
    };
