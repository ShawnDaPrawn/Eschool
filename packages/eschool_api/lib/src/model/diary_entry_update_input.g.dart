// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'diary_entry_update_input.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DiaryEntryUpdateInput _$DiaryEntryUpdateInputFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('DiaryEntryUpdateInput', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['revision', 'title']);
  final val = DiaryEntryUpdateInput(
    revision: $checkedConvert('revision', (v) => (v as num).toInt()),
    title: $checkedConvert('title', (v) => v as String),
    body: $checkedConvert('body', (v) => v as String?),
    date: $checkedConvert(
      'date',
      (v) => v == null ? null : DateTime.parse(v as String),
    ),
    remindAt: $checkedConvert('remind_at', (v) => v as String?),
    done: $checkedConvert('done', (v) => v as bool?),
  );
  return val;
}, fieldKeyMap: const {'remindAt': 'remind_at'});

Map<String, dynamic> _$DiaryEntryUpdateInputToJson(
  DiaryEntryUpdateInput instance,
) => <String, dynamic>{
  'revision': instance.revision,
  'title': instance.title,
  'body': ?instance.body,
  'date': ?instance.date?.toIso8601String(),
  'remind_at': ?instance.remindAt,
  'done': ?instance.done,
};
