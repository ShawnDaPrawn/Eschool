// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'practice_set_entry.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PracticeSetEntry _$PracticeSetEntryFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'PracticeSetEntry',
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
            'definition',
            'review',
          ],
        );
        final val = PracticeSetEntry(
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
          definition: $checkedConvert(
            'definition',
            (v) => PracticeQuestion.fromJson(v as Map<String, dynamic>),
          ),
          review: $checkedConvert(
            'review',
            (v) => v == null
                ? null
                : PracticeReview.fromJson(v as Map<String, dynamic>),
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

Map<String, dynamic> _$PracticeSetEntryToJson(PracticeSetEntry instance) =>
    <String, dynamic>{
      'id': instance.id,
      'item_id': instance.itemId,
      'topic_id': instance.topicId,
      'source_version_id': instance.sourceVersionId,
      'estimated_seconds': instance.estimatedSeconds,
      'definition': instance.definition.toJson(),
      'review': instance.review?.toJson(),
    };
