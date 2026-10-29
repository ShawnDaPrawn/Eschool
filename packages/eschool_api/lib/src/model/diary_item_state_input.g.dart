// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'diary_item_state_input.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DiaryItemStateInput _$DiaryItemStateInputFromJson(Map<String, dynamic> json) =>
    $checkedCreate('DiaryItemStateInput', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['done']);
      final val = DiaryItemStateInput(
        done: $checkedConvert('done', (v) => v as bool),
        remindAt: $checkedConvert('remind_at', (v) => v as String?),
        note: $checkedConvert('note', (v) => v as String?),
      );
      return val;
    }, fieldKeyMap: const {'remindAt': 'remind_at'});

Map<String, dynamic> _$DiaryItemStateInputToJson(
  DiaryItemStateInput instance,
) => <String, dynamic>{
  'done': instance.done,
  'remind_at': ?instance.remindAt,
  'note': ?instance.note,
};
