// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'weekly_digest_action_templates.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WeeklyDigestActionTemplates _$WeeklyDigestActionTemplatesFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('WeeklyDigestActionTemplates', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['practice', 'marks', 'attendance', 'calendar'],
  );
  final val = WeeklyDigestActionTemplates(
    practice: $checkedConvert('practice', (v) => v as String),
    marks: $checkedConvert('marks', (v) => v as String),
    attendance: $checkedConvert('attendance', (v) => v as String),
    calendar: $checkedConvert('calendar', (v) => v as String),
  );
  return val;
});

Map<String, dynamic> _$WeeklyDigestActionTemplatesToJson(
  WeeklyDigestActionTemplates instance,
) => <String, dynamic>{
  'practice': instance.practice,
  'marks': instance.marks,
  'attendance': instance.attendance,
  'calendar': instance.calendar,
};
