// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'practice_review.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PracticeReview _$PracticeReviewFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'PracticeReview',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'id',
            'entry_id',
            'correct',
            'schedule',
            'reviewed_on',
          ],
        );
        final val = PracticeReview(
          id: $checkedConvert('id', (v) => (v as num).toInt()),
          entryId: $checkedConvert('entry_id', (v) => (v as num).toInt()),
          correct: $checkedConvert('correct', (v) => v as bool),
          schedule: $checkedConvert(
            'schedule',
            (v) => PracticeSchedule.fromJson(v as Map<String, dynamic>),
          ),
          reviewedOn: $checkedConvert('reviewed_on', (v) => v as String),
          streakDays: $checkedConvert(
            'streak_days',
            (v) => (v as num?)?.toInt(),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'entryId': 'entry_id',
        'reviewedOn': 'reviewed_on',
        'streakDays': 'streak_days',
      },
    );

Map<String, dynamic> _$PracticeReviewToJson(PracticeReview instance) =>
    <String, dynamic>{
      'id': instance.id,
      'entry_id': instance.entryId,
      'correct': instance.correct,
      'schedule': instance.schedule.toJson(),
      'reviewed_on': instance.reviewedOn,
      'streak_days': ?instance.streakDays,
    };
