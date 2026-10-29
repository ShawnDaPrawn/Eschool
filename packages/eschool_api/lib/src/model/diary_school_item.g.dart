// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'diary_school_item.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DiarySchoolItem _$DiarySchoolItemFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'DiarySchoolItem',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'source',
            'id',
            'title',
            'date',
            'due_at',
            'end_date',
            'subject',
            'homework',
            'state',
          ],
        );
        final val = DiarySchoolItem(
          source_: $checkedConvert(
            'source',
            (v) => $enumDecode(
              _$DiaryItemSourceEnumMap,
              v,
              unknownValue: DiaryItemSource.unknownDefaultOpenApi,
            ),
          ),
          id: $checkedConvert('id', (v) => (v as num).toInt()),
          title: $checkedConvert('title', (v) => v as String),
          date: $checkedConvert('date', (v) => DateTime.parse(v as String)),
          dueAt: $checkedConvert('due_at', (v) => v as String?),
          endDate: $checkedConvert(
            'end_date',
            (v) => v == null ? null : DateTime.parse(v as String),
          ),
          subject: $checkedConvert(
            'subject',
            (v) => v == null
                ? null
                : DiarySubject.fromJson(v as Map<String, dynamic>),
          ),
          homework: $checkedConvert(
            'homework',
            (v) => v == null
                ? null
                : DiaryHomework.fromJson(v as Map<String, dynamic>),
          ),
          state: $checkedConvert(
            'state',
            (v) => DiaryItemLayer.fromJson(v as Map<String, dynamic>),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'source_': 'source',
        'dueAt': 'due_at',
        'endDate': 'end_date',
      },
    );

Map<String, dynamic> _$DiarySchoolItemToJson(DiarySchoolItem instance) =>
    <String, dynamic>{
      'source': _$DiaryItemSourceEnumMap[instance.source_]!,
      'id': instance.id,
      'title': instance.title,
      'date': instance.date.toIso8601String(),
      'due_at': instance.dueAt,
      'end_date': instance.endDate?.toIso8601String(),
      'subject': instance.subject?.toJson(),
      'homework': instance.homework?.toJson(),
      'state': instance.state.toJson(),
    };

const _$DiaryItemSourceEnumMap = {
  DiaryItemSource.homework: 'homework',
  DiaryItemSource.holiday: 'holiday',
  DiaryItemSource.event: 'event',
  DiaryItemSource.unknownDefaultOpenApi: 'unknown_default_open_api',
};
