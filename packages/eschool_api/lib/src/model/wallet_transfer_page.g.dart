// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wallet_transfer_page.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WalletTransferPage _$WalletTransferPageFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'WalletTransferPage',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'school_id',
        'actor_user_id',
        'can_transfer',
        'child_returns',
        'policy_revision',
        'current_page',
        'per_page',
        'total',
        'items',
      ],
    );
    final val = WalletTransferPage(
      schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
      actorUserId: $checkedConvert('actor_user_id', (v) => (v as num).toInt()),
      canTransfer: $checkedConvert('can_transfer', (v) => v as bool),
      childReturns: $checkedConvert(
        'child_returns',
        (v) => $enumDecode(
          _$WalletTransferPageChildReturnsEnumEnumMap,
          v,
          unknownValue:
              WalletTransferPageChildReturnsEnum.unknownDefaultOpenApi,
        ),
      ),
      policyRevision: $checkedConvert(
        'policy_revision',
        (v) => (v as num?)?.toInt(),
      ),
      currentPage: $checkedConvert('current_page', (v) => (v as num).toInt()),
      perPage: $checkedConvert('per_page', (v) => (v as num).toInt()),
      total: $checkedConvert('total', (v) => (v as num).toInt()),
      items: $checkedConvert(
        'items',
        (v) => (v as List<dynamic>)
            .map(
              (e) => WalletTransferReceipt.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'schoolId': 'school_id',
    'actorUserId': 'actor_user_id',
    'canTransfer': 'can_transfer',
    'childReturns': 'child_returns',
    'policyRevision': 'policy_revision',
    'currentPage': 'current_page',
    'perPage': 'per_page',
  },
);

Map<String, dynamic> _$WalletTransferPageToJson(WalletTransferPage instance) =>
    <String, dynamic>{
      'school_id': instance.schoolId,
      'actor_user_id': instance.actorUserId,
      'can_transfer': instance.canTransfer,
      'child_returns':
          _$WalletTransferPageChildReturnsEnumEnumMap[instance.childReturns]!,
      'policy_revision': instance.policyRevision,
      'current_page': instance.currentPage,
      'per_page': instance.perPage,
      'total': instance.total,
      'items': instance.items.map((e) => e.toJson()).toList(),
    };

const _$WalletTransferPageChildReturnsEnumEnumMap = {
  WalletTransferPageChildReturnsEnum.unconfigured: 'unconfigured',
  WalletTransferPageChildReturnsEnum.disabled: 'disabled',
  WalletTransferPageChildReturnsEnum.currentApprovingGuardian:
      'current_approving_guardian',
  WalletTransferPageChildReturnsEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
