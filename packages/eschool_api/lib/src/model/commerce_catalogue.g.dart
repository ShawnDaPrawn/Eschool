// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'commerce_catalogue.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CommerceCatalogue _$CommerceCatalogueFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'CommerceCatalogue',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'school_id',
            'actor_user_id',
            'can_purchase',
            'vendor',
            'items',
            'page',
            'last_page',
          ],
        );
        final val = CommerceCatalogue(
          schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
          actorUserId: $checkedConvert(
            'actor_user_id',
            (v) => (v as num).toInt(),
          ),
          canPurchase: $checkedConvert('can_purchase', (v) => v as bool),
          vendor: $checkedConvert(
            'vendor',
            (v) => CommerceCatalogueVendor.fromJson(v as Map<String, dynamic>),
          ),
          items: $checkedConvert(
            'items',
            (v) => (v as List<dynamic>)
                .map((e) => CommerceProduct.fromJson(e as Map<String, dynamic>))
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
        'canPurchase': 'can_purchase',
        'lastPage': 'last_page',
      },
    );

Map<String, dynamic> _$CommerceCatalogueToJson(CommerceCatalogue instance) =>
    <String, dynamic>{
      'school_id': instance.schoolId,
      'actor_user_id': instance.actorUserId,
      'can_purchase': instance.canPurchase,
      'vendor': instance.vendor.toJson(),
      'items': instance.items.map((e) => e.toJson()).toList(),
      'page': instance.page,
      'last_page': instance.lastPage,
    };
