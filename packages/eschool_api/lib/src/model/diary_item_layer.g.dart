// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'diary_item_layer.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DiaryItemLayer _$DiaryItemLayerFromJson(Map<String, dynamic> json) =>
    $checkedCreate('DiaryItemLayer', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['done', 'done_at', 'remind_at', 'note'],
      );
      final val = DiaryItemLayer(
        done: $checkedConvert('done', (v) => v as bool),
        doneAt: $checkedConvert('done_at', (v) => v as String?),
        remindAt: $checkedConvert('remind_at', (v) => v as String?),
        note: $checkedConvert('note', (v) => v as String?),
      );
      return val;
    }, fieldKeyMap: const {'doneAt': 'done_at', 'remindAt': 'remind_at'});

Map<String, dynamic> _$DiaryItemLayerToJson(DiaryItemLayer instance) =>
    <String, dynamic>{
      'done': instance.done,
      'done_at': instance.doneAt,
      'remind_at': instance.remindAt,
      'note': instance.note,
    };
