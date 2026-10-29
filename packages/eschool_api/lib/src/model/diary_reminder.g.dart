// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'diary_reminder.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DiaryReminder _$DiaryReminderFromJson(Map<String, dynamic> json) =>
    $checkedCreate('DiaryReminder', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['key', 'at', 'text']);
      final val = DiaryReminder(
        key: $checkedConvert('key', (v) => v as String),
        at: $checkedConvert('at', (v) => DateTime.parse(v as String)),
        text: $checkedConvert('text', (v) => v as String),
      );
      return val;
    });

Map<String, dynamic> _$DiaryReminderToJson(DiaryReminder instance) =>
    <String, dynamic>{
      'key': instance.key,
      'at': instance.at.toIso8601String(),
      'text': instance.text,
    };
