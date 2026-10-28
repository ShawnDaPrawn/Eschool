// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wallet_withdrawal_page.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WalletWithdrawalPage _$WalletWithdrawalPageFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'WalletWithdrawalPage',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'school_id',
        'actor_user_id',
        'currency',
        'is_adult',
        'read_only',
        'can_request',
        'policy_revision',
        'cash_threshold_cents',
        'cash_at_boundary',
        'timezone',
        'slots',
        'current_page',
        'per_page',
        'total',
        'items',
      ],
    );
    final val = WalletWithdrawalPage(
      schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
      actorUserId: $checkedConvert('actor_user_id', (v) => (v as num).toInt()),
      currency: $checkedConvert('currency', (v) => v as String?),
      isAdult: $checkedConvert('is_adult', (v) => v as bool),
      readOnly: $checkedConvert('read_only', (v) => v as bool),
      canRequest: $checkedConvert('can_request', (v) => v as bool),
      policyRevision: $checkedConvert(
        'policy_revision',
        (v) => (v as num?)?.toInt(),
      ),
      cashThresholdCents: $checkedConvert(
        'cash_threshold_cents',
        (v) => (v as num?)?.toInt(),
      ),
      cashAtBoundary: $checkedConvert('cash_at_boundary', (v) => v as bool?),
      timezone: $checkedConvert('timezone', (v) => v as String),
      slots: $checkedConvert(
        'slots',
        (v) => (v as List<dynamic>)
            .map(
              (e) => WalletWithdrawalSlot.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
      ),
      currentPage: $checkedConvert('current_page', (v) => (v as num).toInt()),
      perPage: $checkedConvert('per_page', (v) => (v as num).toInt()),
      total: $checkedConvert('total', (v) => (v as num).toInt()),
      items: $checkedConvert(
        'items',
        (v) => (v as List<dynamic>)
            .map(
              (e) =>
                  WalletWithdrawalReceipt.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'schoolId': 'school_id',
    'actorUserId': 'actor_user_id',
    'isAdult': 'is_adult',
    'readOnly': 'read_only',
    'canRequest': 'can_request',
    'policyRevision': 'policy_revision',
    'cashThresholdCents': 'cash_threshold_cents',
    'cashAtBoundary': 'cash_at_boundary',
    'currentPage': 'current_page',
    'perPage': 'per_page',
  },
);

Map<String, dynamic> _$WalletWithdrawalPageToJson(
  WalletWithdrawalPage instance,
) => <String, dynamic>{
  'school_id': instance.schoolId,
  'actor_user_id': instance.actorUserId,
  'currency': instance.currency,
  'is_adult': instance.isAdult,
  'read_only': instance.readOnly,
  'can_request': instance.canRequest,
  'policy_revision': instance.policyRevision,
  'cash_threshold_cents': instance.cashThresholdCents,
  'cash_at_boundary': instance.cashAtBoundary,
  'timezone': instance.timezone,
  'slots': instance.slots.map((e) => e.toJson()).toList(),
  'current_page': instance.currentPage,
  'per_page': instance.perPage,
  'total': instance.total,
  'items': instance.items.map((e) => e.toJson()).toList(),
};
