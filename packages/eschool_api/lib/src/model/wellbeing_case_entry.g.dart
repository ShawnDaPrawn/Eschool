// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wellbeing_case_entry.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WellbeingCaseEntry _$WellbeingCaseEntryFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'WellbeingCaseEntry',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'id',
            'author_id',
            'supersedes_id',
            'kind',
            'body',
            'happened_at',
            'created_at',
            'author_name',
          ],
        );
        final val = WellbeingCaseEntry(
          id: $checkedConvert('id', (v) => (v as num).toInt()),
          authorId: $checkedConvert('author_id', (v) => (v as num).toInt()),
          supersedesId: $checkedConvert(
            'supersedes_id',
            (v) => (v as num?)?.toInt(),
          ),
          kind: $checkedConvert(
            'kind',
            (v) => $enumDecode(
              _$WellbeingCaseEntryKindEnumEnumMap,
              v,
              unknownValue: WellbeingCaseEntryKindEnum.unknownDefaultOpenApi,
            ),
          ),
          body: $checkedConvert('body', (v) => v as String),
          happenedAt: $checkedConvert(
            'happened_at',
            (v) => DateTime.parse(v as String),
          ),
          createdAt: $checkedConvert(
            'created_at',
            (v) => DateTime.parse(v as String),
          ),
          authorName: $checkedConvert('author_name', (v) => v as String),
        );
        return val;
      },
      fieldKeyMap: const {
        'authorId': 'author_id',
        'supersedesId': 'supersedes_id',
        'happenedAt': 'happened_at',
        'createdAt': 'created_at',
        'authorName': 'author_name',
      },
    );

Map<String, dynamic> _$WellbeingCaseEntryToJson(WellbeingCaseEntry instance) =>
    <String, dynamic>{
      'id': instance.id,
      'author_id': instance.authorId,
      'supersedes_id': instance.supersedesId,
      'kind': _$WellbeingCaseEntryKindEnumEnumMap[instance.kind]!,
      'body': instance.body,
      'happened_at': instance.happenedAt.toIso8601String(),
      'created_at': instance.createdAt.toIso8601String(),
      'author_name': instance.authorName,
    };

const _$WellbeingCaseEntryKindEnumEnumMap = {
  WellbeingCaseEntryKindEnum.note: 'note',
  WellbeingCaseEntryKindEnum.escalation: 'escalation',
  WellbeingCaseEntryKindEnum.status: 'status',
  WellbeingCaseEntryKindEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
