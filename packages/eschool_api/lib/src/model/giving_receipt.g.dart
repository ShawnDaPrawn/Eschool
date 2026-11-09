// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'giving_receipt.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GivingReceipt _$GivingReceiptFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'GivingReceipt',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'receipt',
        'donor_name',
        'donor_email',
        'amount_cents',
        'currency',
        'method',
        'source_reference',
        'received_on',
        'pledge_id',
        'ledger_entry_id',
        'campaign',
        'school',
        'issued_at',
      ],
    );
    final val = GivingReceipt(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      receipt: $checkedConvert('receipt', (v) => v as String),
      donorName: $checkedConvert('donor_name', (v) => v as String),
      donorEmail: $checkedConvert('donor_email', (v) => v as String?),
      amountCents: $checkedConvert('amount_cents', (v) => (v as num).toInt()),
      currency: $checkedConvert('currency', (v) => v as String),
      method: $checkedConvert('method', (v) => v as String),
      sourceReference: $checkedConvert('source_reference', (v) => v as String),
      receivedOn: $checkedConvert(
        'received_on',
        (v) => DateTime.parse(v as String),
      ),
      pledgeId: $checkedConvert('pledge_id', (v) => (v as num?)?.toInt()),
      ledgerEntryId: $checkedConvert(
        'ledger_entry_id',
        (v) => (v as num).toInt(),
      ),
      campaign: $checkedConvert('campaign', (v) => v as String),
      school: $checkedConvert('school', (v) => v as String),
      issuedAt: $checkedConvert(
        'issued_at',
        (v) => DateTime.parse(v as String),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'donorName': 'donor_name',
    'donorEmail': 'donor_email',
    'amountCents': 'amount_cents',
    'sourceReference': 'source_reference',
    'receivedOn': 'received_on',
    'pledgeId': 'pledge_id',
    'ledgerEntryId': 'ledger_entry_id',
    'issuedAt': 'issued_at',
  },
);

Map<String, dynamic> _$GivingReceiptToJson(GivingReceipt instance) =>
    <String, dynamic>{
      'id': instance.id,
      'receipt': instance.receipt,
      'donor_name': instance.donorName,
      'donor_email': instance.donorEmail,
      'amount_cents': instance.amountCents,
      'currency': instance.currency,
      'method': instance.method,
      'source_reference': instance.sourceReference,
      'received_on': instance.receivedOn.toIso8601String(),
      'pledge_id': instance.pledgeId,
      'ledger_entry_id': instance.ledgerEntryId,
      'campaign': instance.campaign,
      'school': instance.school,
      'issued_at': instance.issuedAt.toIso8601String(),
    };
