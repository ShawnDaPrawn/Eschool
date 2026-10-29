// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'commerce_quote.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CommerceQuote _$CommerceQuoteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'CommerceQuote',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'school_id',
        'actor_user_id',
        'wallet_id',
        'wallet_owner_id',
        'learner_id',
        'vendor_id',
        'currency',
        'amount_cents',
        'net_cents',
        'vat_cents',
        'seller',
        'lines',
        'quote',
        'expires_at',
      ],
    );
    final val = CommerceQuote(
      schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
      actorUserId: $checkedConvert('actor_user_id', (v) => (v as num).toInt()),
      walletId: $checkedConvert('wallet_id', (v) => (v as num).toInt()),
      walletOwnerId: $checkedConvert(
        'wallet_owner_id',
        (v) => (v as num).toInt(),
      ),
      learnerId: $checkedConvert('learner_id', (v) => (v as num?)?.toInt()),
      vendorId: $checkedConvert('vendor_id', (v) => (v as num).toInt()),
      currency: $checkedConvert('currency', (v) => v as String),
      amountCents: $checkedConvert('amount_cents', (v) => (v as num).toInt()),
      netCents: $checkedConvert('net_cents', (v) => (v as num).toInt()),
      vatCents: $checkedConvert('vat_cents', (v) => (v as num).toInt()),
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
      quote: $checkedConvert('quote', (v) => v as String),
      expiresAt: $checkedConvert(
        'expires_at',
        (v) => DateTime.parse(v as String),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'schoolId': 'school_id',
    'actorUserId': 'actor_user_id',
    'walletId': 'wallet_id',
    'walletOwnerId': 'wallet_owner_id',
    'learnerId': 'learner_id',
    'vendorId': 'vendor_id',
    'amountCents': 'amount_cents',
    'netCents': 'net_cents',
    'vatCents': 'vat_cents',
    'expiresAt': 'expires_at',
  },
);

Map<String, dynamic> _$CommerceQuoteToJson(CommerceQuote instance) =>
    <String, dynamic>{
      'school_id': instance.schoolId,
      'actor_user_id': instance.actorUserId,
      'wallet_id': instance.walletId,
      'wallet_owner_id': instance.walletOwnerId,
      'learner_id': instance.learnerId,
      'vendor_id': instance.vendorId,
      'currency': instance.currency,
      'amount_cents': instance.amountCents,
      'net_cents': instance.netCents,
      'vat_cents': instance.vatCents,
      'seller': instance.seller.toJson(),
      'lines': instance.lines.map((e) => e.toJson()).toList(),
      'quote': instance.quote,
      'expires_at': instance.expiresAt.toIso8601String(),
    };
