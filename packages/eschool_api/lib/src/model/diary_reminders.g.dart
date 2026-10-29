// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'diary_reminders.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DiaryReminders _$DiaryRemindersFromJson(Map<String, dynamic> json) =>
    $checkedCreate('DiaryReminders', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['school_id', 'reader_id', 'reminders'],
      );
      final val = DiaryReminders(
        schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
        readerId: $checkedConvert('reader_id', (v) => (v as num).toInt()),
        reminders: $checkedConvert(
          'reminders',
          (v) => (v as List<dynamic>)
              .map((e) => DiaryReminder.fromJson(e as Map<String, dynamic>))
              .toList(),
        ),
      );
      return val;
    }, fieldKeyMap: const {'schoolId': 'school_id', 'readerId': 'reader_id'});

Map<String, dynamic> _$DiaryRemindersToJson(DiaryReminders instance) =>
    <String, dynamic>{
      'school_id': instance.schoolId,
      'reader_id': instance.readerId,
      'reminders': instance.reminders.map((e) => e.toJson()).toList(),
    };
