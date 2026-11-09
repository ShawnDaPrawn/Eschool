// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'pool_profile_school.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PoolProfileSchool _$PoolProfileSchoolFromJson(Map<String, dynamic> json) =>
    $checkedCreate('PoolProfileSchool', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['id', 'name', 'slug']);
      final val = PoolProfileSchool(
        id: $checkedConvert('id', (v) => (v as num).toInt()),
        name: $checkedConvert('name', (v) => v as String),
        slug: $checkedConvert('slug', (v) => v as String),
      );
      return val;
    });

Map<String, dynamic> _$PoolProfileSchoolToJson(PoolProfileSchool instance) =>
    <String, dynamic>{
      'id': instance.id,
      'name': instance.name,
      'slug': instance.slug,
    };
