// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'practice_test_tutor_review.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PracticeTestTutorReview _$PracticeTestTutorReviewFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'PracticeTestTutorReview',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const ['group_id', 'topic_id', 'source_version_ids'],
    );
    final val = PracticeTestTutorReview(
      groupId: $checkedConvert('group_id', (v) => (v as num).toInt()),
      topicId: $checkedConvert('topic_id', (v) => (v as num).toInt()),
      sourceVersionIds: $checkedConvert(
        'source_version_ids',
        (v) => (v as List<dynamic>).map((e) => (e as num).toInt()).toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'groupId': 'group_id',
    'topicId': 'topic_id',
    'sourceVersionIds': 'source_version_ids',
  },
);

Map<String, dynamic> _$PracticeTestTutorReviewToJson(
  PracticeTestTutorReview instance,
) => <String, dynamic>{
  'group_id': instance.groupId,
  'topic_id': instance.topicId,
  'source_version_ids': instance.sourceVersionIds,
};
