// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'vendor_settlement_page.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

VendorSettlementPage _$VendorSettlementPageFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'VendorSettlementPage',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'school_id',
        'actor_user_id',
        'vendor_id',
        'items',
        'page',
        'last_page',
      ],
    );
    final val = VendorSettlementPage(
      schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
      actorUserId: $checkedConvert('actor_user_id', (v) => (v as num).toInt()),
      vendorId: $checkedConvert('vendor_id', (v) => (v as num).toInt()),
      items: $checkedConvert(
        'items',
        (v) => (v as List<dynamic>)
            .map(
              (e) =>
                  VendorSettlementSummary.fromJson(e as Map<String, dynamic>),
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

Map<String, dynamic> _$VendorSettlementPageToJson(
  VendorSettlementPage instance,
) => <String, dynamic>{
  'school_id': instance.schoolId,
  'actor_user_id': instance.actorUserId,
  'vendor_id': instance.vendorId,
  'items': instance.items.map((e) => e.toJson()).toList(),
  'page': instance.page,
  'last_page': instance.lastPage,
};
