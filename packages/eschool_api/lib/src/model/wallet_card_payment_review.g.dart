// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wallet_card_payment_review.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WalletCardPaymentReview _$WalletCardPaymentReviewFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'WalletCardPaymentReview',
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
        'holder_name',
        'portrait_revision',
      ],
    );
    final val = WalletCardPaymentReview(
      schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
      actorUserId: $checkedConvert('actor_user_id', (v) => (v as num).toInt()),
      id: $checkedConvert('id', (v) => v as String),
      vendorId: $checkedConvert('vendor_id', (v) => (v as num).toInt()),
      kind: $checkedConvert(
        'kind',
        (v) => $enumDecode(
          _$WalletCardPaymentReviewKindEnumEnumMap,
          v,
          unknownValue: WalletCardPaymentReviewKindEnum.unknownDefaultOpenApi,
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
      holderName: $checkedConvert('holder_name', (v) => v as String),
      portraitRevision: $checkedConvert(
        'portrait_revision',
        (v) => (v as num).toInt(),
      ),
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
    'holderName': 'holder_name',
    'portraitRevision': 'portrait_revision',
  },
);

Map<String, dynamic> _$WalletCardPaymentReviewToJson(
  WalletCardPaymentReview instance,
) => <String, dynamic>{
  'school_id': instance.schoolId,
  'actor_user_id': instance.actorUserId,
  'id': instance.id,
  'vendor_id': instance.vendorId,
  'kind': _$WalletCardPaymentReviewKindEnumEnumMap[instance.kind]!,
  'currency': instance.currency,
  'amount_cents': instance.amountCents,
  'net_cents': instance.netCents,
  'vat_cents': instance.vatCents,
  'expires_at': instance.expiresAt.toIso8601String(),
  'seller': instance.seller.toJson(),
  'lines': instance.lines.map((e) => e.toJson()).toList(),
  'holder_name': instance.holderName,
  'portrait_revision': instance.portraitRevision,
};

const _$WalletCardPaymentReviewKindEnumEnumMap = {
  WalletCardPaymentReviewKindEnum.qr: 'qr',
  WalletCardPaymentReviewKindEnum.sun: 'sun',
  WalletCardPaymentReviewKindEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
