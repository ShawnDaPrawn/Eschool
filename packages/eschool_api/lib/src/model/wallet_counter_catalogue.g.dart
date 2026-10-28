// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wallet_counter_catalogue.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WalletCounterCatalogue _$WalletCounterCatalogueFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'WalletCounterCatalogue',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'school_id',
        'actor_user_id',
        'vendor_id',
        'currency',
        'items',
        'page',
        'last_page',
      ],
    );
    final val = WalletCounterCatalogue(
      schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
      actorUserId: $checkedConvert('actor_user_id', (v) => (v as num).toInt()),
      vendorId: $checkedConvert('vendor_id', (v) => (v as num).toInt()),
      currency: $checkedConvert('currency', (v) => v as String),
      items: $checkedConvert(
        'items',
        (v) => (v as List<dynamic>)
            .map(
              (e) => WalletCounterProduct.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
      ),
      page: $checkedConvert('page', (v) => (v as num).toInt()),
      lastPage: $checkedConvert('last_page', (v) => (v as num).toInt()),
    );
    return val;
  },
  fieldKeyMap: const {
    'schoolId': 'school_id',
    'actorUserId': 'actor_user_id',
    'vendorId': 'vendor_id',
    'lastPage': 'last_page',
  },
);

Map<String, dynamic> _$WalletCounterCatalogueToJson(
  WalletCounterCatalogue instance,
) => <String, dynamic>{
  'school_id': instance.schoolId,
  'actor_user_id': instance.actorUserId,
  'vendor_id': instance.vendorId,
  'currency': instance.currency,
  'items': instance.items.map((e) => e.toJson()).toList(),
  'page': instance.page,
  'last_page': instance.lastPage,
};
