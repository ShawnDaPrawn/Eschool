// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'practice_test_result.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PracticeTestResult _$PracticeTestResultFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'PracticeTestResult',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'correct_count',
        'marked_count',
        'question_count',
        'unanswered_count',
        'awaiting_marker_count',
        'memo_available',
        'memo_available_at',
        'topics',
        'review',
      ],
    );
    final val = PracticeTestResult(
      correctCount: $checkedConvert('correct_count', (v) => (v as num).toInt()),
      markedCount: $checkedConvert('marked_count', (v) => (v as num).toInt()),
      questionCount: $checkedConvert(
        'question_count',
        (v) => (v as num).toInt(),
      ),
      unansweredCount: $checkedConvert(
        'unanswered_count',
        (v) => (v as num).toInt(),
      ),
      awaitingMarkerCount: $checkedConvert(
        'awaiting_marker_count',
        (v) => (v as num).toInt(),
      ),
      memoAvailable: $checkedConvert('memo_available', (v) => v as bool),
      memoAvailableAt: $checkedConvert(
        'memo_available_at',
        (v) => v == null ? null : DateTime.parse(v as String),
      ),
      topics: $checkedConvert(
        'topics',
        (v) => (v as List<dynamic>)
            .map(
              (e) =>
                  PracticeTestResultTopic.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
      ),
      review: $checkedConvert(
        'review',
        (v) => PracticeTestReview.fromJson(v as Map<String, dynamic>),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'correctCount': 'correct_count',
    'markedCount': 'marked_count',
    'questionCount': 'question_count',
    'unansweredCount': 'unanswered_count',
    'awaitingMarkerCount': 'awaiting_marker_count',
    'memoAvailable': 'memo_available',
    'memoAvailableAt': 'memo_available_at',
  },
);

Map<String, dynamic> _$PracticeTestResultToJson(PracticeTestResult instance) =>
    <String, dynamic>{
      'correct_count': instance.correctCount,
      'marked_count': instance.markedCount,
      'question_count': instance.questionCount,
      'unanswered_count': instance.unansweredCount,
      'awaiting_marker_count': instance.awaitingMarkerCount,
      'memo_available': instance.memoAvailable,
      'memo_available_at': instance.memoAvailableAt?.toIso8601String(),
      'topics': instance.topics.map((e) => e.toJson()).toList(),
      'review': instance.review.toJson(),
    };
