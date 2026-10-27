// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'parent_fee_statement.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ParentFeeStatement _$ParentFeeStatementFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'ParentFeeStatement',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'school_id',
        'holder_user_id',
        'name',
        'reference',
        'currency',
        'charged_cents',
        'allocated_cents',
        'outstanding_cents',
        'credit_cents',
        'net_cents',
        'next_due_on',
        'scope',
        'charges',
        'receipts',
      ],
    );
    final val = ParentFeeStatement(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
      holderUserId: $checkedConvert(
        'holder_user_id',
        (v) => (v as num).toInt(),
      ),
      name: $checkedConvert('name', (v) => v as String),
      reference: $checkedConvert('reference', (v) => v as String),
      currency: $checkedConvert('currency', (v) => v as String),
      chargedCents: $checkedConvert('charged_cents', (v) => (v as num).toInt()),
      allocatedCents: $checkedConvert(
        'allocated_cents',
        (v) => (v as num).toInt(),
      ),
      outstandingCents: $checkedConvert(
        'outstanding_cents',
        (v) => (v as num).toInt(),
      ),
      creditCents: $checkedConvert('credit_cents', (v) => (v as num).toInt()),
      netCents: $checkedConvert('net_cents', (v) => (v as num).toInt()),
      nextDueOn: $checkedConvert('next_due_on', (v) => v as String?),
      scope: $checkedConvert(
        'scope',
        (v) => $enumDecode(
          _$ParentFeeStatementScopeEnumEnumMap,
          v,
          unknownValue: ParentFeeStatementScopeEnum.unknownDefaultOpenApi,
        ),
      ),
      charges: $checkedConvert(
        'charges',
        (v) => ParentFeeChargePage.fromJson(v as Map<String, dynamic>),
      ),
      receipts: $checkedConvert(
        'receipts',
        (v) => ParentFeeReceiptPage.fromJson(v as Map<String, dynamic>),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'schoolId': 'school_id',
    'holderUserId': 'holder_user_id',
    'chargedCents': 'charged_cents',
    'allocatedCents': 'allocated_cents',
    'outstandingCents': 'outstanding_cents',
    'creditCents': 'credit_cents',
    'netCents': 'net_cents',
    'nextDueOn': 'next_due_on',
  },
);

Map<String, dynamic> _$ParentFeeStatementToJson(ParentFeeStatement instance) =>
    <String, dynamic>{
      'id': instance.id,
      'school_id': instance.schoolId,
      'holder_user_id': instance.holderUserId,
      'name': instance.name,
      'reference': instance.reference,
      'currency': instance.currency,
      'charged_cents': instance.chargedCents,
      'allocated_cents': instance.allocatedCents,
      'outstanding_cents': instance.outstandingCents,
      'credit_cents': instance.creditCents,
      'net_cents': instance.netCents,
      'next_due_on': instance.nextDueOn,
      'scope': _$ParentFeeStatementScopeEnumEnumMap[instance.scope]!,
      'charges': instance.charges.toJson(),
      'receipts': instance.receipts.toJson(),
    };

const _$ParentFeeStatementScopeEnumEnumMap = {
  ParentFeeStatementScopeEnum.yourVisibleLedgerRecords:
      'your_visible_ledger_records',
  ParentFeeStatementScopeEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
