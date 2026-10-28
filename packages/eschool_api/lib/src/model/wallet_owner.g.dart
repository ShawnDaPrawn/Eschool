// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wallet_owner.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WalletOwner _$WalletOwnerFromJson(Map<String, dynamic> json) => $checkedCreate(
  'WalletOwner',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'owner_user_id',
        'owner_name',
        'is_self',
        'can_open',
        'wallets',
      ],
    );
    final val = WalletOwner(
      ownerUserId: $checkedConvert('owner_user_id', (v) => (v as num).toInt()),
      ownerName: $checkedConvert('owner_name', (v) => v as String),
      isSelf: $checkedConvert('is_self', (v) => v as bool),
      canOpen: $checkedConvert('can_open', (v) => v as bool),
      wallets: $checkedConvert(
        'wallets',
        (v) => (v as List<dynamic>)
            .map((e) => WalletSummary.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'ownerUserId': 'owner_user_id',
    'ownerName': 'owner_name',
    'isSelf': 'is_self',
    'canOpen': 'can_open',
  },
);

Map<String, dynamic> _$WalletOwnerToJson(WalletOwner instance) =>
    <String, dynamic>{
      'owner_user_id': instance.ownerUserId,
      'owner_name': instance.ownerName,
      'is_self': instance.isSelf,
      'can_open': instance.canOpen,
      'wallets': instance.wallets.map((e) => e.toJson()).toList(),
    };
