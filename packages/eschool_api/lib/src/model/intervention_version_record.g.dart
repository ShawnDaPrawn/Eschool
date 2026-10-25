// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'intervention_version_record.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

InterventionVersionRecord _$InterventionVersionRecordFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'InterventionVersionRecord',
  json,
  ($checkedConvert) {
    $checkKeys(json, requiredKeys: const ['kind']);
    final val = InterventionVersionRecord(
      kind: $checkedConvert(
        'kind',
        (v) => $enumDecode(
          _$InterventionVersionRecordKindEnumEnumMap,
          v,
          unknownValue: InterventionVersionRecordKindEnum.unknownDefaultOpenApi,
        ),
      ),
      action: $checkedConvert('action', (v) => v as String?),
      outcome: $checkedConvert('outcome', (v) => v as String?),
      reviewOn: $checkedConvert('review_on', (v) => v as String?),
      state: $checkedConvert(
        'state',
        (v) => $enumDecodeNullable(
          _$InterventionVersionRecordStateEnumEnumMap,
          v,
          unknownValue:
              InterventionVersionRecordStateEnum.unknownDefaultOpenApi,
        ),
      ),
      previousOwnerId: $checkedConvert(
        'previous_owner_id',
        (v) => (v as num?)?.toInt(),
      ),
      ownerId: $checkedConvert('owner_id', (v) => (v as num?)?.toInt()),
      reason: $checkedConvert('reason', (v) => v as String?),
    );
    return val;
  },
  fieldKeyMap: const {
    'reviewOn': 'review_on',
    'previousOwnerId': 'previous_owner_id',
    'ownerId': 'owner_id',
  },
);

Map<String, dynamic> _$InterventionVersionRecordToJson(
  InterventionVersionRecord instance,
) => <String, dynamic>{
  'kind': _$InterventionVersionRecordKindEnumEnumMap[instance.kind]!,
  'action': ?instance.action,
  'outcome': ?instance.outcome,
  'review_on': ?instance.reviewOn,
  'state': ?_$InterventionVersionRecordStateEnumEnumMap[instance.state],
  'previous_owner_id': ?instance.previousOwnerId,
  'owner_id': ?instance.ownerId,
  'reason': ?instance.reason,
};

const _$InterventionVersionRecordKindEnumEnumMap = {
  InterventionVersionRecordKindEnum.record: 'record',
  InterventionVersionRecordKindEnum.ownerChange: 'owner_change',
  InterventionVersionRecordKindEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$InterventionVersionRecordStateEnumEnumMap = {
  InterventionVersionRecordStateEnum.active: 'active',
  InterventionVersionRecordStateEnum.completed: 'completed',
  InterventionVersionRecordStateEnum.cancelled: 'cancelled',
  InterventionVersionRecordStateEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
