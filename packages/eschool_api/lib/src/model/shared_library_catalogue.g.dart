// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'shared_library_catalogue.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SharedLibraryCatalogue _$SharedLibraryCatalogueFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SharedLibraryCatalogue', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['items', 'page', 'last_page', 'total', 'filters'],
  );
  final val = SharedLibraryCatalogue(
    items: $checkedConvert(
      'items',
      (v) => (v as List<dynamic>)
          .map(
            (e) => SharedLibraryItemSummary.fromJson(e as Map<String, dynamic>),
          )
          .toList(),
    ),
    page: $checkedConvert('page', (v) => (v as num).toInt()),
    lastPage: $checkedConvert('last_page', (v) => (v as num).toInt()),
    total: $checkedConvert('total', (v) => (v as num).toInt()),
    filters: $checkedConvert(
      'filters',
      (v) => SharedLibraryCatalogueFilters.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
}, fieldKeyMap: const {'lastPage': 'last_page'});

Map<String, dynamic> _$SharedLibraryCatalogueToJson(
  SharedLibraryCatalogue instance,
) => <String, dynamic>{
  'items': instance.items.map((e) => e.toJson()).toList(),
  'page': instance.page,
  'last_page': instance.lastPage,
  'total': instance.total,
  'filters': instance.filters.toJson(),
};
