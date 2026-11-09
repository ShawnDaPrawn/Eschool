// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'reenrolment_learner_contracts_inner.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ReenrolmentLearnerContractsInner _$ReenrolmentLearnerContractsInnerFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'ReenrolmentLearnerContractsInner',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'session_year',
        'version',
        'title',
        'body',
        'text_hash',
        'acceptance_rule',
        'respond_by',
        'status',
        'you_are_fee_responsible',
        'you_accepted_at',
        'can_accept',
        'waiting_for_fee_payer',
      ],
    );
    final val = ReenrolmentLearnerContractsInner(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      sessionYear: $checkedConvert('session_year', (v) => v as String),
      version: $checkedConvert('version', (v) => (v as num).toInt()),
      title: $checkedConvert('title', (v) => v as String),
      body: $checkedConvert('body', (v) => v as String),
      textHash: $checkedConvert('text_hash', (v) => v as String),
      acceptanceRule: $checkedConvert('acceptance_rule', (v) => v as String),
      respondBy: $checkedConvert(
        'respond_by',
        (v) => DateTime.parse(v as String),
      ),
      status: $checkedConvert(
        'status',
        (v) => $enumDecode(
          _$ReenrolmentLearnerContractsInnerStatusEnumEnumMap,
          v,
          unknownValue:
              ReenrolmentLearnerContractsInnerStatusEnum.unknownDefaultOpenApi,
        ),
      ),
      youAreFeeResponsible: $checkedConvert(
        'you_are_fee_responsible',
        (v) => v as bool,
      ),
      youAcceptedAt: $checkedConvert(
        'you_accepted_at',
        (v) => v == null ? null : DateTime.parse(v as String),
      ),
      canAccept: $checkedConvert('can_accept', (v) => v as bool),
      waitingForFeePayer: $checkedConvert(
        'waiting_for_fee_payer',
        (v) => v as bool,
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'sessionYear': 'session_year',
    'textHash': 'text_hash',
    'acceptanceRule': 'acceptance_rule',
    'respondBy': 'respond_by',
    'youAreFeeResponsible': 'you_are_fee_responsible',
    'youAcceptedAt': 'you_accepted_at',
    'canAccept': 'can_accept',
    'waitingForFeePayer': 'waiting_for_fee_payer',
  },
);

Map<String, dynamic> _$ReenrolmentLearnerContractsInnerToJson(
  ReenrolmentLearnerContractsInner instance,
) => <String, dynamic>{
  'id': instance.id,
  'session_year': instance.sessionYear,
  'version': instance.version,
  'title': instance.title,
  'body': instance.body,
  'text_hash': instance.textHash,
  'acceptance_rule': instance.acceptanceRule,
  'respond_by': instance.respondBy.toIso8601String(),
  'status':
      _$ReenrolmentLearnerContractsInnerStatusEnumEnumMap[instance.status]!,
  'you_are_fee_responsible': instance.youAreFeeResponsible,
  'you_accepted_at': instance.youAcceptedAt?.toIso8601String(),
  'can_accept': instance.canAccept,
  'waiting_for_fee_payer': instance.waitingForFeePayer,
};

const _$ReenrolmentLearnerContractsInnerStatusEnumEnumMap = {
  ReenrolmentLearnerContractsInnerStatusEnum.confirmed: 'confirmed',
  ReenrolmentLearnerContractsInnerStatusEnum.awaiting: 'awaiting',
  ReenrolmentLearnerContractsInnerStatusEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
