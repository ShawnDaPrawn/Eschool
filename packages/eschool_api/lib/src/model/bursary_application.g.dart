// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'bursary_application.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

BursaryApplication _$BursaryApplicationFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'BursaryApplication',
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
        'household',
        'documents',
      ],
    );
    final val = BursaryApplication(
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
          _$BursaryApplicationStateEnumEnumMap,
          v,
          unknownValue: BursaryApplicationStateEnum.unknownDefaultOpenApi,
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
          _$BursaryApplicationAwardKindEnumEnumMap,
          v,
          unknownValue: BursaryApplicationAwardKindEnum.unknownDefaultOpenApi,
        ),
      ),
      awardValue: $checkedConvert('award_value', (v) => (v as num?)?.toInt()),
      currency: $checkedConvert('currency', (v) => v as String?),
      erased: $checkedConvert('erased', (v) => v as bool),
      household: $checkedConvert(
        'household',
        (v) => v == null
            ? null
            : BursaryApplicationHousehold.fromJson(v as Map<String, dynamic>),
      ),
      documents: $checkedConvert(
        'documents',
        (v) => (v as List<dynamic>)
            .map(
              (e) => BursaryApplicationDocumentsInner.fromJson(
                e as Map<String, dynamic>,
              ),
            )
            .toList(),
      ),
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
  },
);

Map<String, dynamic> _$BursaryApplicationToJson(
  BursaryApplication instance,
) => <String, dynamic>{
  'id': instance.id,
  'learner_id': instance.learnerId,
  'session_year_id': instance.sessionYearId,
  'session_year': instance.sessionYear,
  'state': _$BursaryApplicationStateEnumEnumMap[instance.state]!,
  'revision': instance.revision,
  'submitted_at': instance.submittedAt?.toIso8601String(),
  'decided_at': instance.decidedAt?.toIso8601String(),
  'award_kind': _$BursaryApplicationAwardKindEnumEnumMap[instance.awardKind],
  'award_value': instance.awardValue,
  'currency': instance.currency,
  'erased': instance.erased,
  'household': instance.household?.toJson(),
  'documents': instance.documents.map((e) => e.toJson()).toList(),
};

const _$BursaryApplicationStateEnumEnumMap = {
  BursaryApplicationStateEnum.draft: 'draft',
  BursaryApplicationStateEnum.submitted: 'submitted',
  BursaryApplicationStateEnum.awarded: 'awarded',
  BursaryApplicationStateEnum.declined: 'declined',
  BursaryApplicationStateEnum.withdrawn: 'withdrawn',
  BursaryApplicationStateEnum.expired: 'expired',
  BursaryApplicationStateEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};

const _$BursaryApplicationAwardKindEnumEnumMap = {
  BursaryApplicationAwardKindEnum.fixed: 'fixed',
  BursaryApplicationAwardKindEnum.percent: 'percent',
  BursaryApplicationAwardKindEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
