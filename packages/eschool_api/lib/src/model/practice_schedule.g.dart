// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'practice_schedule.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PracticeSchedule _$PracticeScheduleFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'PracticeSchedule',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'version',
            'rating',
            'difficulty',
            'stability_days',
            'retrievability_before',
            'interval_days',
          ],
        );
        final val = PracticeSchedule(
          version: $checkedConvert('version', (v) => v as String),
          rating: $checkedConvert('rating', (v) => (v as num).toInt()),
          difficulty: $checkedConvert('difficulty', (v) => v as num),
          stabilityDays: $checkedConvert('stability_days', (v) => v as num),
          retrievabilityBefore: $checkedConvert(
            'retrievability_before',
            (v) => v as num?,
          ),
          intervalDays: $checkedConvert(
            'interval_days',
            (v) => (v as num).toInt(),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'stabilityDays': 'stability_days',
        'retrievabilityBefore': 'retrievability_before',
        'intervalDays': 'interval_days',
      },
    );

Map<String, dynamic> _$PracticeScheduleToJson(PracticeSchedule instance) =>
    <String, dynamic>{
      'version': instance.version,
      'rating': instance.rating,
      'difficulty': instance.difficulty,
      'stability_days': instance.stabilityDays,
      'retrievability_before': instance.retrievabilityBefore,
      'interval_days': instance.intervalDays,
    };
