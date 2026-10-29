// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'diary_entry.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DiaryEntry _$DiaryEntryFromJson(Map<String, dynamic> json) => $checkedCreate(
  'DiaryEntry',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'kind',
        'title',
        'body',
        'date',
        'remind_at',
        'done',
        'done_on',
        'revision',
        'updated_at',
      ],
    );
    final val = DiaryEntry(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      kind: $checkedConvert(
        'kind',
        (v) => $enumDecode(
          _$DiaryEntryKindEnumEnumMap,
          v,
          unknownValue: DiaryEntryKindEnum.unknownDefaultOpenApi,
        ),
      ),
      title: $checkedConvert('title', (v) => v as String),
      body: $checkedConvert('body', (v) => v as String?),
      date: $checkedConvert(
        'date',
        (v) => v == null ? null : DateTime.parse(v as String),
      ),
      remindAt: $checkedConvert('remind_at', (v) => v as String?),
      done: $checkedConvert('done', (v) => v as bool),
      doneOn: $checkedConvert(
        'done_on',
        (v) => v == null ? null : DateTime.parse(v as String),
      ),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      updatedAt: $checkedConvert('updated_at', (v) => v as String?),
    );
    return val;
  },
  fieldKeyMap: const {
    'remindAt': 'remind_at',
    'doneOn': 'done_on',
    'updatedAt': 'updated_at',
  },
);

Map<String, dynamic> _$DiaryEntryToJson(DiaryEntry instance) =>
    <String, dynamic>{
      'id': instance.id,
      'kind': _$DiaryEntryKindEnumEnumMap[instance.kind]!,
      'title': instance.title,
      'body': instance.body,
      'date': instance.date?.toIso8601String(),
      'remind_at': instance.remindAt,
      'done': instance.done,
      'done_on': instance.doneOn?.toIso8601String(),
      'revision': instance.revision,
      'updated_at': instance.updatedAt,
    };

const _$DiaryEntryKindEnumEnumMap = {
  DiaryEntryKindEnum.note: 'note',
  DiaryEntryKindEnum.todo: 'todo',
  DiaryEntryKindEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
