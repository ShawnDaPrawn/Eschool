// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wellbeing_case_entry_input.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WellbeingCaseEntryInput _$WellbeingCaseEntryInputFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'WellbeingCaseEntryInput',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'operation_id',
        'revision',
        'kind',
        'body',
        'happened_at',
      ],
    );
    final val = WellbeingCaseEntryInput(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      kind: $checkedConvert(
        'kind',
        (v) => $enumDecode(
          _$WellbeingCaseEntryInputKindEnumEnumMap,
          v,
          unknownValue: WellbeingCaseEntryInputKindEnum.unknownDefaultOpenApi,
        ),
      ),
      body: $checkedConvert('body', (v) => v as String),
      happenedAt: $checkedConvert(
        'happened_at',
        (v) => DateTime.parse(v as String),
      ),
      supersedesId: $checkedConvert(
        'supersedes_id',
        (v) => (v as num?)?.toInt(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'operationId': 'operation_id',
    'happenedAt': 'happened_at',
    'supersedesId': 'supersedes_id',
  },
);

Map<String, dynamic> _$WellbeingCaseEntryInputToJson(
  WellbeingCaseEntryInput instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'revision': instance.revision,
  'kind': _$WellbeingCaseEntryInputKindEnumEnumMap[instance.kind]!,
  'body': instance.body,
  'happened_at': instance.happenedAt.toIso8601String(),
  'supersedes_id': ?instance.supersedesId,
};

const _$WellbeingCaseEntryInputKindEnumEnumMap = {
  WellbeingCaseEntryInputKindEnum.note: 'note',
  WellbeingCaseEntryInputKindEnum.escalation: 'escalation',
  WellbeingCaseEntryInputKindEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
