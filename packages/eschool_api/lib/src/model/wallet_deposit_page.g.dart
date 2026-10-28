// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wallet_deposit_page.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WalletDepositPage _$WalletDepositPageFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'WalletDepositPage',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'school_id',
        'actor_user_id',
        'currency',
        'is_parent',
        'read_only',
        'can_submit',
        'cash_allowed',
        'eft_allowed',
        'current_page',
        'per_page',
        'total',
        'items',
      ],
    );
    final val = WalletDepositPage(
      schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
      actorUserId: $checkedConvert('actor_user_id', (v) => (v as num).toInt()),
      currency: $checkedConvert('currency', (v) => v as String?),
      isParent: $checkedConvert('is_parent', (v) => v as bool),
      readOnly: $checkedConvert('read_only', (v) => v as bool),
      canSubmit: $checkedConvert('can_submit', (v) => v as bool),
      cashAllowed: $checkedConvert('cash_allowed', (v) => v as bool),
      eftAllowed: $checkedConvert('eft_allowed', (v) => v as bool),
      currentPage: $checkedConvert('current_page', (v) => (v as num).toInt()),
      perPage: $checkedConvert('per_page', (v) => (v as num).toInt()),
      total: $checkedConvert('total', (v) => (v as num).toInt()),
      items: $checkedConvert(
        'items',
        (v) => (v as List<dynamic>)
            .map(
              (e) => WalletDepositReceipt.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'schoolId': 'school_id',
    'actorUserId': 'actor_user_id',
    'isParent': 'is_parent',
    'readOnly': 'read_only',
    'canSubmit': 'can_submit',
    'cashAllowed': 'cash_allowed',
    'eftAllowed': 'eft_allowed',
    'currentPage': 'current_page',
    'perPage': 'per_page',
  },
);

Map<String, dynamic> _$WalletDepositPageToJson(WalletDepositPage instance) =>
    <String, dynamic>{
      'school_id': instance.schoolId,
      'actor_user_id': instance.actorUserId,
      'currency': instance.currency,
      'is_parent': instance.isParent,
      'read_only': instance.readOnly,
      'can_submit': instance.canSubmit,
      'cash_allowed': instance.cashAllowed,
      'eft_allowed': instance.eftAllowed,
      'current_page': instance.currentPage,
      'per_page': instance.perPage,
      'total': instance.total,
      'items': instance.items.map((e) => e.toJson()).toList(),
    };
