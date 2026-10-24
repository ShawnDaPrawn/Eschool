// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'content_year.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ContentYear _$ContentYearFromJson(Map<String, dynamic> json) =>
    $checkedCreate('ContentYear', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['id', 'name']);
      final val = ContentYear(
        id: $checkedConvert('id', (v) => (v as num).toInt()),
        name: $checkedConvert('name', (v) => v as String),
      );
      return val;
    });

Map<String, dynamic> _$ContentYearToJson(ContentYear instance) =>
    <String, dynamic>{'id': instance.id, 'name': instance.name};
