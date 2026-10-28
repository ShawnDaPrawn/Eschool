// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wallet_counter_payment_resolution.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WalletCounterPaymentResolution _$WalletCounterPaymentResolutionFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'WalletCounterPaymentResolution',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'school_id',
        'actor_user_id',
        'vendor_id',
        'operation_id',
        'status',
        'payment',
      ],
    );
    final val = WalletCounterPaymentResolution(
      schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
      actorUserId: $checkedConvert('actor_user_id', (v) => (v as num).toInt()),
      vendorId: $checkedConvert('vendor_id', (v) => (v as num).toInt()),
      operationId: $checkedConvert('operation_id', (v) => v as String),
      status: $checkedConvert(
        'status',
        (v) => $enumDecode(
          _$WalletCounterPaymentResolutionStatusEnumEnumMap,
          v,
          unknownValue:
              WalletCounterPaymentResolutionStatusEnum.unknownDefaultOpenApi,
        ),
      ),
      payment: $checkedConvert(
        'payment',
        (v) => v == null
            ? null
            : WalletCounterPaymentReceipt.fromJson(v as Map<String, dynamic>),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'schoolId': 'school_id',
    'actorUserId': 'actor_user_id',
    'vendorId': 'vendor_id',
    'operationId': 'operation_id',
  },
);

Map<String, dynamic> _$WalletCounterPaymentResolutionToJson(
  WalletCounterPaymentResolution instance,
) => <String, dynamic>{
  'school_id': instance.schoolId,
  'actor_user_id': instance.actorUserId,
  'vendor_id': instance.vendorId,
  'operation_id': instance.operationId,
  'status': _$WalletCounterPaymentResolutionStatusEnumEnumMap[instance.status]!,
  'payment': instance.payment?.toJson(),
};

const _$WalletCounterPaymentResolutionStatusEnumEnumMap = {
  WalletCounterPaymentResolutionStatusEnum.committed: 'committed',
  WalletCounterPaymentResolutionStatusEnum.abandoned: 'abandoned',
  WalletCounterPaymentResolutionStatusEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
