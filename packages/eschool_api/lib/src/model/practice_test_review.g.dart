// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'practice_test_review.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PracticeTestReview _$PracticeTestReviewFromJson(Map<String, dynamic> json) =>
    $checkedCreate('PracticeTestReview', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['topic_ids', 'tutor']);
      final val = PracticeTestReview(
        topicIds: $checkedConvert(
          'topic_ids',
          (v) => (v as List<dynamic>).map((e) => (e as num).toInt()).toList(),
        ),
        tutor: $checkedConvert(
          'tutor',
          (v) => v == null
              ? null
              : PracticeTestTutorReview.fromJson(v as Map<String, dynamic>),
        ),
      );
      return val;
    }, fieldKeyMap: const {'topicIds': 'topic_ids'});

Map<String, dynamic> _$PracticeTestReviewToJson(PracticeTestReview instance) =>
    <String, dynamic>{
      'topic_ids': instance.topicIds,
      'tutor': instance.tutor?.toJson(),
    };
