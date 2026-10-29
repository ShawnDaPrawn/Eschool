// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'admission_counter_ticket.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AdmissionCounterTicket _$AdmissionCounterTicketFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'AdmissionCounterTicket',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'school_id',
        'actor_user_id',
        'vendor_id',
        'id',
        'revision',
        'status',
        'event_name',
        'product_name',
        'kind',
        'named_learner_name',
        'starts_at',
        'ends_at',
        'can_redeem',
        'redeemed_at',
        'redeemed_by',
      ],
    );
    final val = AdmissionCounterTicket(
      schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
      actorUserId: $checkedConvert('actor_user_id', (v) => (v as num).toInt()),
      vendorId: $checkedConvert('vendor_id', (v) => (v as num).toInt()),
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      status: $checkedConvert(
        'status',
        (v) => $enumDecode(
          _$AdmissionCounterTicketStatusEnumEnumMap,
          v,
          unknownValue: AdmissionCounterTicketStatusEnum.unknownDefaultOpenApi,
        ),
      ),
      eventName: $checkedConvert('event_name', (v) => v as String),
      productName: $checkedConvert('product_name', (v) => v as String),
      kind: $checkedConvert(
        'kind',
        (v) => $enumDecode(
          _$AdmissionCounterTicketKindEnumEnumMap,
          v,
          unknownValue: AdmissionCounterTicketKindEnum.unknownDefaultOpenApi,
        ),
      ),
      namedLearnerName: $checkedConvert(
        'named_learner_name',
        (v) => v as String?,
      ),
      startsAt: $checkedConvert(
        'starts_at',
        (v) => DateTime.parse(v as String),
      ),
      endsAt: $checkedConvert('ends_at', (v) => DateTime.parse(v as String)),
      canRedeem: $checkedConvert('can_redeem', (v) => v as bool),
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
    'vendorId': 'vendor_id',
    'eventName': 'event_name',
    'productName': 'product_name',
    'namedLearnerName': 'named_learner_name',
    'startsAt': 'starts_at',
    'endsAt': 'ends_at',
    'canRedeem': 'can_redeem',
    'redeemedAt': 'redeemed_at',
    'redeemedBy': 'redeemed_by',
  },
);

Map<String, dynamic> _$AdmissionCounterTicketToJson(
  AdmissionCounterTicket instance,
) => <String, dynamic>{
  'school_id': instance.schoolId,
  'actor_user_id': instance.actorUserId,
  'vendor_id': instance.vendorId,
  'id': instance.id,
  'revision': instance.revision,
  'status': _$AdmissionCounterTicketStatusEnumEnumMap[instance.status]!,
  'event_name': instance.eventName,
  'product_name': instance.productName,
  'kind': _$AdmissionCounterTicketKindEnumEnumMap[instance.kind]!,
  'named_learner_name': instance.namedLearnerName,
  'starts_at': instance.startsAt.toIso8601String(),
  'ends_at': instance.endsAt.toIso8601String(),
  'can_redeem': instance.canRedeem,
  'redeemed_at': instance.redeemedAt?.toIso8601String(),
  'redeemed_by': instance.redeemedBy,
};

const _$AdmissionCounterTicketStatusEnumEnumMap = {
  AdmissionCounterTicketStatusEnum.valid: 'valid',
  AdmissionCounterTicketStatusEnum.redeemed: 'redeemed',
  AdmissionCounterTicketStatusEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$AdmissionCounterTicketKindEnumEnumMap = {
  AdmissionCounterTicketKindEnum.general: 'general',
  AdmissionCounterTicketKindEnum.named: 'named',
  AdmissionCounterTicketKindEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
