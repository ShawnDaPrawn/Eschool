// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'weekly_digest_practice.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WeeklyDigestPractice _$WeeklyDigestPracticeFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('WeeklyDigestPractice', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['total', 'completed']);
  final val = WeeklyDigestPractice(
    total: $checkedConvert('total', (v) => (v as num).toInt()),
    completed: $checkedConvert('completed', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$WeeklyDigestPracticeToJson(
  WeeklyDigestPractice instance,
) => <String, dynamic>{
  'total': instance.total,
  'completed': instance.completed,
};
