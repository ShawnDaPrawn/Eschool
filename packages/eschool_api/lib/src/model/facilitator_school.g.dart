// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'facilitator_school.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FacilitatorSchool _$FacilitatorSchoolFromJson(Map<String, dynamic> json) =>
    $checkedCreate('FacilitatorSchool', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['id', 'name', 'slug']);
      final val = FacilitatorSchool(
        id: $checkedConvert('id', (v) => (v as num).toInt()),
        name: $checkedConvert('name', (v) => v as String),
        slug: $checkedConvert('slug', (v) => v as String),
      );
      return val;
    });

Map<String, dynamic> _$FacilitatorSchoolToJson(FacilitatorSchool instance) =>
    <String, dynamic>{
      'id': instance.id,
      'name': instance.name,
      'slug': instance.slug,
    };
