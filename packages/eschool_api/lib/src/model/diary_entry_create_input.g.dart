// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'diary_entry_create_input.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DiaryEntryCreateInput _$DiaryEntryCreateInputFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('DiaryEntryCreateInput', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['operation_id', 'kind', 'title']);
  final val = DiaryEntryCreateInput(
    operationId: $checkedConvert('operation_id', (v) => v as String),
    kind: $checkedConvert(
      'kind',
      (v) => $enumDecode(
        _$DiaryEntryCreateInputKindEnumEnumMap,
        v,
        unknownValue: DiaryEntryCreateInputKindEnum.unknownDefaultOpenApi,
      ),
    ),
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
}, fieldKeyMap: const {'operationId': 'operation_id', 'remindAt': 'remind_at'});

Map<String, dynamic> _$DiaryEntryCreateInputToJson(
  DiaryEntryCreateInput instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'kind': _$DiaryEntryCreateInputKindEnumEnumMap[instance.kind]!,
  'title': instance.title,
  'body': ?instance.body,
  'date': ?instance.date?.toIso8601String(),
  'remind_at': ?instance.remindAt,
  'done': ?instance.done,
};

const _$DiaryEntryCreateInputKindEnumEnumMap = {
  DiaryEntryCreateInputKindEnum.note: 'note',
  DiaryEntryCreateInputKindEnum.todo: 'todo',
  DiaryEntryCreateInputKindEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
