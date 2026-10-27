// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'parent_fee_charge.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ParentFeeCharge _$ParentFeeChargeFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'ParentFeeCharge',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'invoice_line_id',
            'invoice_number',
            'learner_id',
            'learner_name',
            'description',
            'period_start',
            'period_end',
            'issued_on',
            'due_on',
            'currency',
            'charged_cents',
            'allocated_cents',
            'outstanding_cents',
          ],
        );
        final val = ParentFeeCharge(
          invoiceLineId: $checkedConvert(
            'invoice_line_id',
            (v) => (v as num).toInt(),
          ),
          invoiceNumber: $checkedConvert('invoice_number', (v) => v as String),
          learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
          learnerName: $checkedConvert('learner_name', (v) => v as String),
          description: $checkedConvert('description', (v) => v as String),
          periodStart: $checkedConvert('period_start', (v) => v as String),
          periodEnd: $checkedConvert('period_end', (v) => v as String),
          issuedOn: $checkedConvert('issued_on', (v) => v as String),
          dueOn: $checkedConvert('due_on', (v) => v as String),
          currency: $checkedConvert('currency', (v) => v as String),
          chargedCents: $checkedConvert(
            'charged_cents',
            (v) => (v as num).toInt(),
          ),
          allocatedCents: $checkedConvert(
            'allocated_cents',
            (v) => (v as num).toInt(),
          ),
          outstandingCents: $checkedConvert(
            'outstanding_cents',
            (v) => (v as num).toInt(),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'invoiceLineId': 'invoice_line_id',
        'invoiceNumber': 'invoice_number',
        'learnerId': 'learner_id',
        'learnerName': 'learner_name',
        'periodStart': 'period_start',
        'periodEnd': 'period_end',
        'issuedOn': 'issued_on',
        'dueOn': 'due_on',
        'chargedCents': 'charged_cents',
        'allocatedCents': 'allocated_cents',
        'outstandingCents': 'outstanding_cents',
      },
    );

Map<String, dynamic> _$ParentFeeChargeToJson(ParentFeeCharge instance) =>
    <String, dynamic>{
      'invoice_line_id': instance.invoiceLineId,
      'invoice_number': instance.invoiceNumber,
      'learner_id': instance.learnerId,
      'learner_name': instance.learnerName,
      'description': instance.description,
      'period_start': instance.periodStart,
      'period_end': instance.periodEnd,
      'issued_on': instance.issuedOn,
      'due_on': instance.dueOn,
      'currency': instance.currency,
      'charged_cents': instance.chargedCents,
      'allocated_cents': instance.allocatedCents,
      'outstanding_cents': instance.outstandingCents,
    };
