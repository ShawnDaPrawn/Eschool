// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wallet_desk_receipt_page.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WalletDeskReceiptPage _$WalletDeskReceiptPageFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'WalletDeskReceiptPage',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'school_id',
        'actor_user_id',
        'wallet_id',
        'owner_user_id',
        'currency',
        'read_only',
        'current_page',
        'per_page',
        'total',
        'items',
      ],
    );
    final val = WalletDeskReceiptPage(
      schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
      actorUserId: $checkedConvert('actor_user_id', (v) => (v as num).toInt()),
      walletId: $checkedConvert('wallet_id', (v) => (v as num).toInt()),
      ownerUserId: $checkedConvert('owner_user_id', (v) => (v as num).toInt()),
      currency: $checkedConvert('currency', (v) => v as String),
      readOnly: $checkedConvert('read_only', (v) => v as bool),
      currentPage: $checkedConvert('current_page', (v) => (v as num).toInt()),
      perPage: $checkedConvert('per_page', (v) => (v as num).toInt()),
      total: $checkedConvert('total', (v) => (v as num).toInt()),
      items: $checkedConvert(
        'items',
        (v) => (v as List<dynamic>)
            .map((e) => WalletDeskReceipt.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'schoolId': 'school_id',
    'actorUserId': 'actor_user_id',
    'walletId': 'wallet_id',
    'ownerUserId': 'owner_user_id',
    'readOnly': 'read_only',
    'currentPage': 'current_page',
    'perPage': 'per_page',
  },
);

Map<String, dynamic> _$WalletDeskReceiptPageToJson(
  WalletDeskReceiptPage instance,
) => <String, dynamic>{
  'school_id': instance.schoolId,
  'actor_user_id': instance.actorUserId,
  'wallet_id': instance.walletId,
  'owner_user_id': instance.ownerUserId,
  'currency': instance.currency,
  'read_only': instance.readOnly,
  'current_page': instance.currentPage,
  'per_page': instance.perPage,
  'total': instance.total,
  'items': instance.items.map((e) => e.toJson()).toList(),
};
