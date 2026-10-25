// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wellbeing_case_entry_receipt_entry.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WellbeingCaseEntryReceiptEntry _$WellbeingCaseEntryReceiptEntryFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'WellbeingCaseEntryReceiptEntry',
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
      ],
    );
    final val = WellbeingCaseEntryReceiptEntry(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      authorId: $checkedConvert('author_id', (v) => (v as num).toInt()),
      supersedesId: $checkedConvert(
        'supersedes_id',
        (v) => (v as num?)?.toInt(),
      ),
      kind: $checkedConvert(
        'kind',
        (v) => $enumDecode(
          _$WellbeingCaseEntryReceiptEntryKindEnumEnumMap,
          v,
          unknownValue:
              WellbeingCaseEntryReceiptEntryKindEnum.unknownDefaultOpenApi,
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
    );
    return val;
  },
  fieldKeyMap: const {
    'authorId': 'author_id',
    'supersedesId': 'supersedes_id',
    'happenedAt': 'happened_at',
    'createdAt': 'created_at',
  },
);

Map<String, dynamic> _$WellbeingCaseEntryReceiptEntryToJson(
  WellbeingCaseEntryReceiptEntry instance,
) => <String, dynamic>{
  'id': instance.id,
  'author_id': instance.authorId,
  'supersedes_id': instance.supersedesId,
  'kind': _$WellbeingCaseEntryReceiptEntryKindEnumEnumMap[instance.kind]!,
  'body': instance.body,
  'happened_at': instance.happenedAt.toIso8601String(),
  'created_at': instance.createdAt.toIso8601String(),
};

const _$WellbeingCaseEntryReceiptEntryKindEnumEnumMap = {
  WellbeingCaseEntryReceiptEntryKindEnum.note: 'note',
  WellbeingCaseEntryReceiptEntryKindEnum.escalation: 'escalation',
  WellbeingCaseEntryReceiptEntryKindEnum.status: 'status',
  WellbeingCaseEntryReceiptEntryKindEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
