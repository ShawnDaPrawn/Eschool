// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'commerce_order_page.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CommerceOrderPage _$CommerceOrderPageFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'CommerceOrderPage',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'school_id',
            'actor_user_id',
            'read_only',
            'items',
            'page',
            'last_page',
          ],
        );
        final val = CommerceOrderPage(
          schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
          actorUserId: $checkedConvert(
            'actor_user_id',
            (v) => (v as num).toInt(),
          ),
          readOnly: $checkedConvert('read_only', (v) => v as bool),
          items: $checkedConvert(
            'items',
            (v) => (v as List<dynamic>)
                .map(
                  (e) =>
                      CommerceOrderReceipt.fromJson(e as Map<String, dynamic>),
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
        'readOnly': 'read_only',
        'lastPage': 'last_page',
      },
    );

Map<String, dynamic> _$CommerceOrderPageToJson(CommerceOrderPage instance) =>
    <String, dynamic>{
      'school_id': instance.schoolId,
      'actor_user_id': instance.actorUserId,
      'read_only': instance.readOnly,
      'items': instance.items.map((e) => e.toJson()).toList(),
      'page': instance.page,
      'last_page': instance.lastPage,
    };
