// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'commerce_vendor_page.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CommerceVendorPage _$CommerceVendorPageFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'CommerceVendorPage',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'school_id',
        'actor_user_id',
        'can_purchase',
        'items',
        'page',
        'last_page',
      ],
    );
    final val = CommerceVendorPage(
      walletPaymentsWritable: $checkedConvert(
        'wallet_payments_writable',
        (v) => v as bool?,
      ),
      walletPaymentsAvailable: $checkedConvert(
        'wallet_payments_available',
        (v) => v as bool?,
      ),
      schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
      actorUserId: $checkedConvert('actor_user_id', (v) => (v as num).toInt()),
      canPurchase: $checkedConvert('can_purchase', (v) => v as bool),
      items: $checkedConvert(
        'items',
        (v) => (v as List<dynamic>)
            .map((e) => CommerceVendorBrief.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
      page: $checkedConvert('page', (v) => (v as num).toInt()),
      lastPage: $checkedConvert('last_page', (v) => (v as num).toInt()),
      ticketsAvailable: $checkedConvert('tickets_available', (v) => v as bool?),
    );
    return val;
  },
  fieldKeyMap: const {
    'walletPaymentsWritable': 'wallet_payments_writable',
    'walletPaymentsAvailable': 'wallet_payments_available',
    'schoolId': 'school_id',
    'actorUserId': 'actor_user_id',
    'canPurchase': 'can_purchase',
    'lastPage': 'last_page',
    'ticketsAvailable': 'tickets_available',
  },
);

Map<String, dynamic> _$CommerceVendorPageToJson(CommerceVendorPage instance) =>
    <String, dynamic>{
      'wallet_payments_writable': ?instance.walletPaymentsWritable,
      'wallet_payments_available': ?instance.walletPaymentsAvailable,
      'school_id': instance.schoolId,
      'actor_user_id': instance.actorUserId,
      'can_purchase': instance.canPurchase,
      'items': instance.items.map((e) => e.toJson()).toList(),
      'page': instance.page,
      'last_page': instance.lastPage,
      'tickets_available': ?instance.ticketsAvailable,
    };
