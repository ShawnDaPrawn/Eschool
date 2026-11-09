// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'reenrolment_contract_list_items_inner.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ReenrolmentContractListItemsInner _$ReenrolmentContractListItemsInnerFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'ReenrolmentContractListItemsInner',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'session_year_id',
        'session_year',
        'version',
        'title',
        'text_hash',
        'acceptance_rule',
        'respond_by',
        'state',
        'revision',
        'published_at',
        'confirmed',
      ],
    );
    final val = ReenrolmentContractListItemsInner(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      sessionYearId: $checkedConvert(
        'session_year_id',
        (v) => (v as num).toInt(),
      ),
      sessionYear: $checkedConvert('session_year', (v) => v as String),
      version: $checkedConvert('version', (v) => (v as num).toInt()),
      title: $checkedConvert('title', (v) => v as String),
      textHash: $checkedConvert('text_hash', (v) => v as String),
      acceptanceRule: $checkedConvert('acceptance_rule', (v) => v as String),
      respondBy: $checkedConvert(
        'respond_by',
        (v) => DateTime.parse(v as String),
      ),
      state: $checkedConvert(
        'state',
        (v) => $enumDecode(
          _$ReenrolmentContractListItemsInnerStateEnumEnumMap,
          v,
          unknownValue:
              ReenrolmentContractListItemsInnerStateEnum.unknownDefaultOpenApi,
        ),
      ),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      publishedAt: $checkedConvert(
        'published_at',
        (v) => DateTime.parse(v as String),
      ),
      confirmed: $checkedConvert('confirmed', (v) => (v as num).toInt()),
    );
    return val;
  },
  fieldKeyMap: const {
    'sessionYearId': 'session_year_id',
    'sessionYear': 'session_year',
    'textHash': 'text_hash',
    'acceptanceRule': 'acceptance_rule',
    'respondBy': 'respond_by',
    'publishedAt': 'published_at',
  },
);

Map<String, dynamic> _$ReenrolmentContractListItemsInnerToJson(
  ReenrolmentContractListItemsInner instance,
) => <String, dynamic>{
  'id': instance.id,
  'session_year_id': instance.sessionYearId,
  'session_year': instance.sessionYear,
  'version': instance.version,
  'title': instance.title,
  'text_hash': instance.textHash,
  'acceptance_rule': instance.acceptanceRule,
  'respond_by': instance.respondBy.toIso8601String(),
  'state': _$ReenrolmentContractListItemsInnerStateEnumEnumMap[instance.state]!,
  'revision': instance.revision,
  'published_at': instance.publishedAt.toIso8601String(),
  'confirmed': instance.confirmed,
};

const _$ReenrolmentContractListItemsInnerStateEnumEnumMap = {
  ReenrolmentContractListItemsInnerStateEnum.open: 'open',
  ReenrolmentContractListItemsInnerStateEnum.superseded: 'superseded',
  ReenrolmentContractListItemsInnerStateEnum.withdrawn: 'withdrawn',
  ReenrolmentContractListItemsInnerStateEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
