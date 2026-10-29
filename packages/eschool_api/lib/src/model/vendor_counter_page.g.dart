// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'vendor_counter_page.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

VendorCounterPage _$VendorCounterPageFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'VendorCounterPage',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'school_id',
            'actor_user_id',
            'vendor_id',
            'status',
            'items',
            'page',
            'last_page',
          ],
        );
        final val = VendorCounterPage(
          schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
          actorUserId: $checkedConvert(
            'actor_user_id',
            (v) => (v as num).toInt(),
          ),
          vendorId: $checkedConvert('vendor_id', (v) => (v as num).toInt()),
          status: $checkedConvert(
            'status',
            (v) => $enumDecode(
              _$VendorCounterPageStatusEnumEnumMap,
              v,
              unknownValue: VendorCounterPageStatusEnum.unknownDefaultOpenApi,
            ),
          ),
          items: $checkedConvert(
            'items',
            (v) => (v as List<dynamic>)
                .map(
                  (e) =>
                      VendorCounterReceipt.fromJson(e as Map<String, dynamic>),
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

Map<String, dynamic> _$VendorCounterPageToJson(VendorCounterPage instance) =>
    <String, dynamic>{
      'school_id': instance.schoolId,
      'actor_user_id': instance.actorUserId,
      'vendor_id': instance.vendorId,
      'status': _$VendorCounterPageStatusEnumEnumMap[instance.status]!,
      'items': instance.items.map((e) => e.toJson()).toList(),
      'page': instance.page,
      'last_page': instance.lastPage,
    };

const _$VendorCounterPageStatusEnumEnumMap = {
  VendorCounterPageStatusEnum.ready: 'ready',
  VendorCounterPageStatusEnum.collected: 'collected',
  VendorCounterPageStatusEnum.cancelled: 'cancelled',
  VendorCounterPageStatusEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
