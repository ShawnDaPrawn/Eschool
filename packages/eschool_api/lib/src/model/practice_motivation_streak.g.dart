// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'practice_motivation_streak.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PracticeMotivationStreak _$PracticeMotivationStreakFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'PracticeMotivationStreak',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const ['current_days', 'best_days', 'rest_day_available'],
    );
    final val = PracticeMotivationStreak(
      currentDays: $checkedConvert('current_days', (v) => (v as num).toInt()),
      bestDays: $checkedConvert('best_days', (v) => (v as num).toInt()),
      restDayAvailable: $checkedConvert('rest_day_available', (v) => v as bool),
    );
    return val;
  },
  fieldKeyMap: const {
    'currentDays': 'current_days',
    'bestDays': 'best_days',
    'restDayAvailable': 'rest_day_available',
  },
);

Map<String, dynamic> _$PracticeMotivationStreakToJson(
  PracticeMotivationStreak instance,
) => <String, dynamic>{
  'current_days': instance.currentDays,
  'best_days': instance.bestDays,
  'rest_day_available': instance.restDayAvailable,
};
