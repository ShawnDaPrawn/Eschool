// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'admission_ticket.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AdmissionTicket _$AdmissionTicketFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'AdmissionTicket',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'school_id',
        'actor_user_id',
        'id',
        'holder_user_id',
        'revision',
        'status',
        'can_transfer',
        'event',
        'type_id',
        'kind',
        'transfer_rule',
        'product_name',
        'currency',
        'unit_price_cents',
        'order_number',
        'named_learner_id',
        'named_learner_name',
        'qr',
        'ticket_number',
        'issued_at',
        'redeemed_at',
        'redeemed_by',
      ],
    );
    final val = AdmissionTicket(
      schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
      actorUserId: $checkedConvert('actor_user_id', (v) => (v as num).toInt()),
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      holderUserId: $checkedConvert(
        'holder_user_id',
        (v) => (v as num).toInt(),
      ),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      status: $checkedConvert(
        'status',
        (v) => $enumDecode(
          _$AdmissionTicketStatusEnumEnumMap,
          v,
          unknownValue: AdmissionTicketStatusEnum.unknownDefaultOpenApi,
        ),
      ),
      canTransfer: $checkedConvert('can_transfer', (v) => v as bool),
      event: $checkedConvert(
        'event',
        (v) => AdmissionEvent.fromJson(v as Map<String, dynamic>),
      ),
      typeId: $checkedConvert('type_id', (v) => (v as num).toInt()),
      kind: $checkedConvert(
        'kind',
        (v) => $enumDecode(
          _$AdmissionTicketKindEnumEnumMap,
          v,
          unknownValue: AdmissionTicketKindEnum.unknownDefaultOpenApi,
        ),
      ),
      transferRule: $checkedConvert(
        'transfer_rule',
        (v) => $enumDecode(
          _$AdmissionTicketTransferRuleEnumEnumMap,
          v,
          unknownValue: AdmissionTicketTransferRuleEnum.unknownDefaultOpenApi,
        ),
      ),
      productName: $checkedConvert('product_name', (v) => v as String),
      currency: $checkedConvert('currency', (v) => v as String),
      unitPriceCents: $checkedConvert(
        'unit_price_cents',
        (v) => (v as num).toInt(),
      ),
      orderNumber: $checkedConvert('order_number', (v) => v as String),
      namedLearnerId: $checkedConvert(
        'named_learner_id',
        (v) => (v as num?)?.toInt(),
      ),
      namedLearnerName: $checkedConvert(
        'named_learner_name',
        (v) => v as String?,
      ),
      qr: $checkedConvert('qr', (v) => v as String?),
      ticketNumber: $checkedConvert('ticket_number', (v) => v as String?),
      issuedAt: $checkedConvert(
        'issued_at',
        (v) => DateTime.parse(v as String),
      ),
      redeemedAt: $checkedConvert(
        'redeemed_at',
        (v) => v == null ? null : DateTime.parse(v as String),
      ),
      redeemedBy: $checkedConvert('redeemed_by', (v) => (v as num?)?.toInt()),
    );
    return val;
  },
  fieldKeyMap: const {
    'schoolId': 'school_id',
    'actorUserId': 'actor_user_id',
    'holderUserId': 'holder_user_id',
    'canTransfer': 'can_transfer',
    'typeId': 'type_id',
    'transferRule': 'transfer_rule',
    'productName': 'product_name',
    'unitPriceCents': 'unit_price_cents',
    'orderNumber': 'order_number',
    'namedLearnerId': 'named_learner_id',
    'namedLearnerName': 'named_learner_name',
    'ticketNumber': 'ticket_number',
    'issuedAt': 'issued_at',
    'redeemedAt': 'redeemed_at',
    'redeemedBy': 'redeemed_by',
  },
);

Map<String, dynamic> _$AdmissionTicketToJson(AdmissionTicket instance) =>
    <String, dynamic>{
      'school_id': instance.schoolId,
      'actor_user_id': instance.actorUserId,
      'id': instance.id,
      'holder_user_id': instance.holderUserId,
      'revision': instance.revision,
      'status': _$AdmissionTicketStatusEnumEnumMap[instance.status]!,
      'can_transfer': instance.canTransfer,
      'event': instance.event.toJson(),
      'type_id': instance.typeId,
      'kind': _$AdmissionTicketKindEnumEnumMap[instance.kind]!,
      'transfer_rule':
          _$AdmissionTicketTransferRuleEnumEnumMap[instance.transferRule]!,
      'product_name': instance.productName,
      'currency': instance.currency,
      'unit_price_cents': instance.unitPriceCents,
      'order_number': instance.orderNumber,
      'named_learner_id': instance.namedLearnerId,
      'named_learner_name': instance.namedLearnerName,
      'qr': instance.qr,
      'ticket_number': instance.ticketNumber,
      'issued_at': instance.issuedAt.toIso8601String(),
      'redeemed_at': instance.redeemedAt?.toIso8601String(),
      'redeemed_by': instance.redeemedBy,
    };

const _$AdmissionTicketStatusEnumEnumMap = {
  AdmissionTicketStatusEnum.valid: 'valid',
  AdmissionTicketStatusEnum.redeemed: 'redeemed',
  AdmissionTicketStatusEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};

const _$AdmissionTicketKindEnumEnumMap = {
  AdmissionTicketKindEnum.general: 'general',
  AdmissionTicketKindEnum.named: 'named',
  AdmissionTicketKindEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};

const _$AdmissionTicketTransferRuleEnumEnumMap = {
  AdmissionTicketTransferRuleEnum.none: 'none',
  AdmissionTicketTransferRuleEnum.families: 'families',
  AdmissionTicketTransferRuleEnum.walletProfiles: 'wallet_profiles',
  AdmissionTicketTransferRuleEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
