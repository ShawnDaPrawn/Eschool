// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'giving_donors_pledges_inner.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GivingDonorsPledgesInner _$GivingDonorsPledgesInnerFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'GivingDonorsPledgesInner',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'reference',
        'donor_name',
        'donor_email',
        'amount_cents',
        'currency',
        'pledged_on',
        'received_cents',
      ],
    );
    final val = GivingDonorsPledgesInner(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      reference: $checkedConvert('reference', (v) => v as String),
      donorName: $checkedConvert('donor_name', (v) => v as String),
      donorEmail: $checkedConvert('donor_email', (v) => v as String?),
      amountCents: $checkedConvert('amount_cents', (v) => (v as num).toInt()),
      currency: $checkedConvert('currency', (v) => v as String),
      pledgedOn: $checkedConvert(
        'pledged_on',
        (v) => DateTime.parse(v as String),
      ),
      receivedCents: $checkedConvert(
        'received_cents',
        (v) => (v as num).toInt(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'donorName': 'donor_name',
    'donorEmail': 'donor_email',
    'amountCents': 'amount_cents',
    'pledgedOn': 'pledged_on',
    'receivedCents': 'received_cents',
  },
);

Map<String, dynamic> _$GivingDonorsPledgesInnerToJson(
  GivingDonorsPledgesInner instance,
) => <String, dynamic>{
  'id': instance.id,
  'reference': instance.reference,
  'donor_name': instance.donorName,
  'donor_email': instance.donorEmail,
  'amount_cents': instance.amountCents,
  'currency': instance.currency,
  'pledged_on': instance.pledgedOn.toIso8601String(),
  'received_cents': instance.receivedCents,
};
