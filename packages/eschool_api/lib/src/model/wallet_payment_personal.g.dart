// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wallet_payment_personal.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WalletPaymentPersonal _$WalletPaymentPersonalFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'WalletPaymentPersonal',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'school_id',
        'actor_user_id',
        'id',
        'vendor_id',
        'kind',
        'currency',
        'amount_cents',
        'net_cents',
        'vat_cents',
        'expires_at',
        'seller',
        'lines',
        'status',
        'qr',
        'order_id',
        'wallet_id',
        'wallet_owner_id',
        'wallet_owner_name',
      ],
    );
    final val = WalletPaymentPersonal(
      schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
      actorUserId: $checkedConvert('actor_user_id', (v) => (v as num).toInt()),
      id: $checkedConvert('id', (v) => v as String),
      vendorId: $checkedConvert('vendor_id', (v) => (v as num).toInt()),
      kind: $checkedConvert(
        'kind',
        (v) => $enumDecode(
          _$WalletPaymentPersonalKindEnumEnumMap,
          v,
          unknownValue: WalletPaymentPersonalKindEnum.unknownDefaultOpenApi,
        ),
      ),
      currency: $checkedConvert('currency', (v) => v as String),
      amountCents: $checkedConvert('amount_cents', (v) => (v as num).toInt()),
      netCents: $checkedConvert('net_cents', (v) => (v as num).toInt()),
      vatCents: $checkedConvert('vat_cents', (v) => (v as num).toInt()),
      expiresAt: $checkedConvert(
        'expires_at',
        (v) => DateTime.parse(v as String),
      ),
      seller: $checkedConvert(
        'seller',
        (v) => CommerceSeller.fromJson(v as Map<String, dynamic>),
      ),
      lines: $checkedConvert(
        'lines',
        (v) => (v as List<dynamic>)
            .map((e) => CommerceQuoteLine.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
      status: $checkedConvert(
        'status',
        (v) => $enumDecode(
          _$WalletPaymentPersonalStatusEnumEnumMap,
          v,
          unknownValue: WalletPaymentPersonalStatusEnum.unknownDefaultOpenApi,
        ),
      ),
      qr: $checkedConvert('qr', (v) => v as String?),
      orderId: $checkedConvert('order_id', (v) => (v as num?)?.toInt()),
      walletId: $checkedConvert('wallet_id', (v) => (v as num).toInt()),
      walletOwnerId: $checkedConvert(
        'wallet_owner_id',
        (v) => (v as num).toInt(),
      ),
      walletOwnerName: $checkedConvert('wallet_owner_name', (v) => v as String),
    );
    return val;
  },
  fieldKeyMap: const {
    'schoolId': 'school_id',
    'actorUserId': 'actor_user_id',
    'vendorId': 'vendor_id',
    'amountCents': 'amount_cents',
    'netCents': 'net_cents',
    'vatCents': 'vat_cents',
    'expiresAt': 'expires_at',
    'orderId': 'order_id',
    'walletId': 'wallet_id',
    'walletOwnerId': 'wallet_owner_id',
    'walletOwnerName': 'wallet_owner_name',
  },
);

Map<String, dynamic> _$WalletPaymentPersonalToJson(
  WalletPaymentPersonal instance,
) => <String, dynamic>{
  'school_id': instance.schoolId,
  'actor_user_id': instance.actorUserId,
  'id': instance.id,
  'vendor_id': instance.vendorId,
  'kind': _$WalletPaymentPersonalKindEnumEnumMap[instance.kind]!,
  'currency': instance.currency,
  'amount_cents': instance.amountCents,
  'net_cents': instance.netCents,
  'vat_cents': instance.vatCents,
  'expires_at': instance.expiresAt.toIso8601String(),
  'seller': instance.seller.toJson(),
  'lines': instance.lines.map((e) => e.toJson()).toList(),
  'status': _$WalletPaymentPersonalStatusEnumEnumMap[instance.status]!,
  'qr': instance.qr,
  'order_id': instance.orderId,
  'wallet_id': instance.walletId,
  'wallet_owner_id': instance.walletOwnerId,
  'wallet_owner_name': instance.walletOwnerName,
};

const _$WalletPaymentPersonalKindEnumEnumMap = {
  WalletPaymentPersonalKindEnum.qr: 'qr',
  WalletPaymentPersonalKindEnum.sun: 'sun',
  WalletPaymentPersonalKindEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$WalletPaymentPersonalStatusEnumEnumMap = {
  WalletPaymentPersonalStatusEnum.awaitingCounter: 'awaiting_counter',
  WalletPaymentPersonalStatusEnum.paid: 'paid',
  WalletPaymentPersonalStatusEnum.expired: 'expired',
  WalletPaymentPersonalStatusEnum.cancelled: 'cancelled',
  WalletPaymentPersonalStatusEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
