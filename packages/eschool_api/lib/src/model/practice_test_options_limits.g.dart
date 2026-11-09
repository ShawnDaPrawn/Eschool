// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'practice_test_options_limits.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PracticeTestOptionsLimits _$PracticeTestOptionsLimitsFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'PracticeTestOptionsLimits',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'topics_max',
        'questions_min',
        'questions_max',
        'minutes_min',
        'minutes_max',
        'window_days_max',
      ],
    );
    final val = PracticeTestOptionsLimits(
      topicsMax: $checkedConvert('topics_max', (v) => (v as num).toInt()),
      questionsMin: $checkedConvert('questions_min', (v) => (v as num).toInt()),
      questionsMax: $checkedConvert('questions_max', (v) => (v as num).toInt()),
      minutesMin: $checkedConvert('minutes_min', (v) => (v as num).toInt()),
      minutesMax: $checkedConvert('minutes_max', (v) => (v as num).toInt()),
      windowDaysMax: $checkedConvert(
        'window_days_max',
        (v) => (v as num).toInt(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'topicsMax': 'topics_max',
    'questionsMin': 'questions_min',
    'questionsMax': 'questions_max',
    'minutesMin': 'minutes_min',
    'minutesMax': 'minutes_max',
    'windowDaysMax': 'window_days_max',
  },
);

Map<String, dynamic> _$PracticeTestOptionsLimitsToJson(
  PracticeTestOptionsLimits instance,
) => <String, dynamic>{
  'topics_max': instance.topicsMax,
  'questions_min': instance.questionsMin,
  'questions_max': instance.questionsMax,
  'minutes_min': instance.minutesMin,
  'minutes_max': instance.minutesMax,
  'window_days_max': instance.windowDaysMax,
};
