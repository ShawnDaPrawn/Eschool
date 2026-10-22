// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'pagination.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

Pagination _$PaginationFromJson(Map<String, dynamic> json) => $checkedCreate(
  'Pagination',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const ['page', 'per_page', 'total', 'last_page'],
    );
    final val = Pagination(
      page: $checkedConvert('page', (v) => (v as num).toInt()),
      perPage: $checkedConvert('per_page', (v) => (v as num).toInt()),
      total: $checkedConvert('total', (v) => (v as num).toInt()),
      lastPage: $checkedConvert('last_page', (v) => (v as num).toInt()),
    );
    return val;
  },
  fieldKeyMap: const {'perPage': 'per_page', 'lastPage': 'last_page'},
);

Map<String, dynamic> _$PaginationToJson(Pagination instance) =>
    <String, dynamic>{
      'page': instance.page,
      'per_page': instance.perPage,
      'total': instance.total,
      'last_page': instance.lastPage,
    };
