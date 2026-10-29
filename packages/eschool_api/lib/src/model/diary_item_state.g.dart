// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'diary_item_state.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DiaryItemState _$DiaryItemStateFromJson(Map<String, dynamic> json) =>
    $checkedCreate('DiaryItemState', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['source', 'id', 'done', 'remind_at', 'note'],
      );
      final val = DiaryItemState(
        source_: $checkedConvert(
          'source',
          (v) => $enumDecode(
            _$DiaryItemSourceEnumMap,
            v,
            unknownValue: DiaryItemSource.unknownDefaultOpenApi,
          ),
        ),
        id: $checkedConvert('id', (v) => (v as num).toInt()),
        done: $checkedConvert('done', (v) => v as bool),
        remindAt: $checkedConvert('remind_at', (v) => v as String?),
        note: $checkedConvert('note', (v) => v as String?),
      );
      return val;
    }, fieldKeyMap: const {'source_': 'source', 'remindAt': 'remind_at'});

Map<String, dynamic> _$DiaryItemStateToJson(DiaryItemState instance) =>
    <String, dynamic>{
      'source': _$DiaryItemSourceEnumMap[instance.source_]!,
      'id': instance.id,
      'done': instance.done,
      'remind_at': instance.remindAt,
      'note': instance.note,
    };

const _$DiaryItemSourceEnumMap = {
  DiaryItemSource.homework: 'homework',
  DiaryItemSource.holiday: 'holiday',
  DiaryItemSource.event: 'event',
  DiaryItemSource.unknownDefaultOpenApi: 'unknown_default_open_api',
};
