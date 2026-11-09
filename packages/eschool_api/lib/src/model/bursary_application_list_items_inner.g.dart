// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'bursary_application_list_items_inner.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

BursaryApplicationListItemsInner _$BursaryApplicationListItemsInnerFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'BursaryApplicationListItemsInner',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'learner_id',
        'session_year_id',
        'session_year',
        'state',
        'revision',
        'submitted_at',
        'decided_at',
        'award_kind',
        'award_value',
        'currency',
        'erased',
        'learner_name',
      ],
    );
    final val = BursaryApplicationListItemsInner(
      id: $checkedConvert('id', (v) => v as String),
      learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
      sessionYearId: $checkedConvert(
        'session_year_id',
        (v) => (v as num).toInt(),
      ),
      sessionYear: $checkedConvert('session_year', (v) => v as String),
      state: $checkedConvert(
        'state',
        (v) => $enumDecode(
          _$BursaryApplicationListItemsInnerStateEnumEnumMap,
          v,
          unknownValue:
              BursaryApplicationListItemsInnerStateEnum.unknownDefaultOpenApi,
        ),
      ),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      submittedAt: $checkedConvert(
        'submitted_at',
        (v) => v == null ? null : DateTime.parse(v as String),
      ),
      decidedAt: $checkedConvert(
        'decided_at',
        (v) => v == null ? null : DateTime.parse(v as String),
      ),
      awardKind: $checkedConvert(
        'award_kind',
        (v) => $enumDecodeNullable(
          _$BursaryApplicationListItemsInnerAwardKindEnumEnumMap,
          v,
          unknownValue: BursaryApplicationListItemsInnerAwardKindEnum
              .unknownDefaultOpenApi,
        ),
      ),
      awardValue: $checkedConvert('award_value', (v) => (v as num?)?.toInt()),
      currency: $checkedConvert('currency', (v) => v as String?),
      erased: $checkedConvert('erased', (v) => v as bool),
      learnerName: $checkedConvert('learner_name', (v) => v as String),
    );
    return val;
  },
  fieldKeyMap: const {
    'learnerId': 'learner_id',
    'sessionYearId': 'session_year_id',
    'sessionYear': 'session_year',
    'submittedAt': 'submitted_at',
    'decidedAt': 'decided_at',
    'awardKind': 'award_kind',
    'awardValue': 'award_value',
    'learnerName': 'learner_name',
  },
);

Map<String, dynamic> _$BursaryApplicationListItemsInnerToJson(
  BursaryApplicationListItemsInner instance,
) => <String, dynamic>{
  'id': instance.id,
  'learner_id': instance.learnerId,
  'session_year_id': instance.sessionYearId,
  'session_year': instance.sessionYear,
  'state': _$BursaryApplicationListItemsInnerStateEnumEnumMap[instance.state]!,
  'revision': instance.revision,
  'submitted_at': instance.submittedAt?.toIso8601String(),
  'decided_at': instance.decidedAt?.toIso8601String(),
  'award_kind':
      _$BursaryApplicationListItemsInnerAwardKindEnumEnumMap[instance
          .awardKind],
  'award_value': instance.awardValue,
  'currency': instance.currency,
  'erased': instance.erased,
  'learner_name': instance.learnerName,
};

const _$BursaryApplicationListItemsInnerStateEnumEnumMap = {
  BursaryApplicationListItemsInnerStateEnum.draft: 'draft',
  BursaryApplicationListItemsInnerStateEnum.submitted: 'submitted',
  BursaryApplicationListItemsInnerStateEnum.awarded: 'awarded',
  BursaryApplicationListItemsInnerStateEnum.declined: 'declined',
  BursaryApplicationListItemsInnerStateEnum.withdrawn: 'withdrawn',
  BursaryApplicationListItemsInnerStateEnum.expired: 'expired',
  BursaryApplicationListItemsInnerStateEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$BursaryApplicationListItemsInnerAwardKindEnumEnumMap = {
  BursaryApplicationListItemsInnerAwardKindEnum.fixed: 'fixed',
  BursaryApplicationListItemsInnerAwardKindEnum.percent: 'percent',
  BursaryApplicationListItemsInnerAwardKindEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
