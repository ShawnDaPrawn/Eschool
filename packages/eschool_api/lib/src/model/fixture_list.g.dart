// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'fixture_list.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FixtureList _$FixtureListFromJson(Map<String, dynamic> json) =>
    $checkedCreate('FixtureList', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['items', 'page', 'last_page', 'total'],
      );
      final val = FixtureList(
        items: $checkedConvert(
          'items',
          (v) => (v as List<dynamic>)
              .map((e) => FixtureSummary.fromJson(e as Map<String, dynamic>))
              .toList(),
        ),
        page: $checkedConvert('page', (v) => (v as num).toInt()),
        lastPage: $checkedConvert('last_page', (v) => (v as num).toInt()),
        total: $checkedConvert('total', (v) => (v as num).toInt()),
      );
      return val;
    }, fieldKeyMap: const {'lastPage': 'last_page'});

Map<String, dynamic> _$FixtureListToJson(FixtureList instance) =>
    <String, dynamic>{
      'items': instance.items.map((e) => e.toJson()).toList(),
      'page': instance.page,
      'last_page': instance.lastPage,
      'total': instance.total,
    };
