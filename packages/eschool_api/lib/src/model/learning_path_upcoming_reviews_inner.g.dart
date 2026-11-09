// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'learning_path_upcoming_reviews_inner.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

LearningPathUpcomingReviewsInner _$LearningPathUpcomingReviewsInnerFromJson(
  Map<String, dynamic> json,
) =>
    $checkedCreate('LearningPathUpcomingReviewsInner', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['topic_id', 'name', 'due_on']);
      final val = LearningPathUpcomingReviewsInner(
        topicId: $checkedConvert('topic_id', (v) => (v as num).toInt()),
        name: $checkedConvert('name', (v) => v as String),
        dueOn: $checkedConvert('due_on', (v) => v as String),
      );
      return val;
    }, fieldKeyMap: const {'topicId': 'topic_id', 'dueOn': 'due_on'});

Map<String, dynamic> _$LearningPathUpcomingReviewsInnerToJson(
  LearningPathUpcomingReviewsInner instance,
) => <String, dynamic>{
  'topic_id': instance.topicId,
  'name': instance.name,
  'due_on': instance.dueOn,
};
