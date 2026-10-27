// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'parent_fee_credit_note.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ParentFeeCreditNote _$ParentFeeCreditNoteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'ParentFeeCreditNote',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'number',
        'invoice_line_id',
        'invoice_number',
        'learner_id',
        'learner_name',
        'currency',
        'amount_cents',
        'unpaid_cents',
        'released_cents',
        'reason_code',
        'issued_on',
      ],
    );
    final val = ParentFeeCreditNote(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      number: $checkedConvert('number', (v) => v as String),
      invoiceLineId: $checkedConvert(
        'invoice_line_id',
        (v) => (v as num).toInt(),
      ),
      invoiceNumber: $checkedConvert('invoice_number', (v) => v as String),
      learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
      learnerName: $checkedConvert('learner_name', (v) => v as String),
      currency: $checkedConvert('currency', (v) => v as String),
      amountCents: $checkedConvert('amount_cents', (v) => (v as num).toInt()),
      unpaidCents: $checkedConvert('unpaid_cents', (v) => (v as num).toInt()),
      releasedCents: $checkedConvert(
        'released_cents',
        (v) => (v as num).toInt(),
      ),
      reasonCode: $checkedConvert(
        'reason_code',
        (v) => $enumDecode(
          _$ParentFeeCreditNoteReasonCodeEnumEnumMap,
          v,
          unknownValue: ParentFeeCreditNoteReasonCodeEnum.unknownDefaultOpenApi,
        ),
      ),
      issuedOn: $checkedConvert('issued_on', (v) => v as String),
    );
    return val;
  },
  fieldKeyMap: const {
    'invoiceLineId': 'invoice_line_id',
    'invoiceNumber': 'invoice_number',
    'learnerId': 'learner_id',
    'learnerName': 'learner_name',
    'amountCents': 'amount_cents',
    'unpaidCents': 'unpaid_cents',
    'releasedCents': 'released_cents',
    'reasonCode': 'reason_code',
    'issuedOn': 'issued_on',
  },
);

Map<String, dynamic> _$ParentFeeCreditNoteToJson(
  ParentFeeCreditNote instance,
) => <String, dynamic>{
  'id': instance.id,
  'number': instance.number,
  'invoice_line_id': instance.invoiceLineId,
  'invoice_number': instance.invoiceNumber,
  'learner_id': instance.learnerId,
  'learner_name': instance.learnerName,
  'currency': instance.currency,
  'amount_cents': instance.amountCents,
  'unpaid_cents': instance.unpaidCents,
  'released_cents': instance.releasedCents,
  'reason_code':
      _$ParentFeeCreditNoteReasonCodeEnumEnumMap[instance.reasonCode]!,
  'issued_on': instance.issuedOn,
};

const _$ParentFeeCreditNoteReasonCodeEnumEnumMap = {
  ParentFeeCreditNoteReasonCodeEnum.billingError: 'billing_error',
  ParentFeeCreditNoteReasonCodeEnum.feeReduction: 'fee_reduction',
  ParentFeeCreditNoteReasonCodeEnum.leaverCredit: 'leaver_credit',
  ParentFeeCreditNoteReasonCodeEnum.other: 'other',
  ParentFeeCreditNoteReasonCodeEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
