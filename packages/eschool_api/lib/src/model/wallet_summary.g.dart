// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wallet_summary.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WalletSummary _$WalletSummaryFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'WalletSummary',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'school_id',
        'owner_user_id',
        'owner_name',
        'currency',
        'balance_cents',
        'is_child',
        'read_only',
        'can_manage_limits',
        'school_daily_spend_cents',
        'child_limits_required',
        'limits',
      ],
    );
    final val = WalletSummary(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
      ownerUserId: $checkedConvert('owner_user_id', (v) => (v as num).toInt()),
      ownerName: $checkedConvert('owner_name', (v) => v as String),
      currency: $checkedConvert('currency', (v) => v as String),
      balanceCents: $checkedConvert('balance_cents', (v) => (v as num).toInt()),
      isChild: $checkedConvert('is_child', (v) => v as bool),
      readOnly: $checkedConvert('read_only', (v) => v as bool),
      canManageLimits: $checkedConvert('can_manage_limits', (v) => v as bool),
      schoolDailySpendCents: $checkedConvert(
        'school_daily_spend_cents',
        (v) => (v as num).toInt(),
      ),
      childLimitsRequired: $checkedConvert(
        'child_limits_required',
        (v) => v as bool,
      ),
      limits: $checkedConvert(
        'limits',
        (v) => WalletControls.fromJson(v as Map<String, dynamic>),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'schoolId': 'school_id',
    'ownerUserId': 'owner_user_id',
    'ownerName': 'owner_name',
    'balanceCents': 'balance_cents',
    'isChild': 'is_child',
    'readOnly': 'read_only',
    'canManageLimits': 'can_manage_limits',
    'schoolDailySpendCents': 'school_daily_spend_cents',
    'childLimitsRequired': 'child_limits_required',
  },
);

Map<String, dynamic> _$WalletSummaryToJson(WalletSummary instance) =>
    <String, dynamic>{
      'id': instance.id,
      'school_id': instance.schoolId,
      'owner_user_id': instance.ownerUserId,
      'owner_name': instance.ownerName,
      'currency': instance.currency,
      'balance_cents': instance.balanceCents,
      'is_child': instance.isChild,
      'read_only': instance.readOnly,
      'can_manage_limits': instance.canManageLimits,
      'school_daily_spend_cents': instance.schoolDailySpendCents,
      'child_limits_required': instance.childLimitsRequired,
      'limits': instance.limits.toJson(),
    };
