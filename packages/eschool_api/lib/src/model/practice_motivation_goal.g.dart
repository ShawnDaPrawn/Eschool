// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'practice_motivation_goal.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PracticeMotivationGoal _$PracticeMotivationGoalFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'PracticeMotivationGoal',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'days_per_week',
        'practised_days_this_week',
        'met',
        'week_start',
        'revision',
      ],
    );
    final val = PracticeMotivationGoal(
      daysPerWeek: $checkedConvert(
        'days_per_week',
        (v) => (v as num?)?.toInt(),
      ),
      practisedDaysThisWeek: $checkedConvert(
        'practised_days_this_week',
        (v) => (v as num).toInt(),
      ),
      met: $checkedConvert('met', (v) => v as bool),
      weekStart: $checkedConvert(
        'week_start',
        (v) => DateTime.parse(v as String),
      ),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
    );
    return val;
  },
  fieldKeyMap: const {
    'daysPerWeek': 'days_per_week',
    'practisedDaysThisWeek': 'practised_days_this_week',
    'weekStart': 'week_start',
  },
);

Map<String, dynamic> _$PracticeMotivationGoalToJson(
  PracticeMotivationGoal instance,
) => <String, dynamic>{
  'days_per_week': instance.daysPerWeek,
  'practised_days_this_week': instance.practisedDaysThisWeek,
  'met': instance.met,
  'week_start': instance.weekStart.toIso8601String(),
  'revision': instance.revision,
};
