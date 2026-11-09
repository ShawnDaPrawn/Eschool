// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'pool_member_page.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PoolMemberPage _$PoolMemberPageFromJson(Map<String, dynamic> json) =>
    $checkedCreate('PoolMemberPage', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['items', 'page', 'last_page', 'total'],
      );
      final val = PoolMemberPage(
        items: $checkedConvert(
          'items',
          (v) => (v as List<dynamic>)
              .map((e) => PoolMemberSummary.fromJson(e as Map<String, dynamic>))
              .toList(),
        ),
        page: $checkedConvert('page', (v) => (v as num).toInt()),
        lastPage: $checkedConvert('last_page', (v) => (v as num).toInt()),
        total: $checkedConvert('total', (v) => (v as num).toInt()),
      );
      return val;
    }, fieldKeyMap: const {'lastPage': 'last_page'});

Map<String, dynamic> _$PoolMemberPageToJson(PoolMemberPage instance) =>
    <String, dynamic>{
      'items': instance.items.map((e) => e.toJson()).toList(),
      'page': instance.page,
      'last_page': instance.lastPage,
      'total': instance.total,
    };
