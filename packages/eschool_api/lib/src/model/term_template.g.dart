// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'term_template.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TermTemplate _$TermTemplateFromJson(Map<String, dynamic> json) =>
    $checkedCreate('TermTemplate', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['id', 'name', 'base_year', 'term_count'],
      );
      final val = TermTemplate(
        id: $checkedConvert('id', (v) => (v as num).toInt()),
        name: $checkedConvert('name', (v) => v as String),
        baseYear: $checkedConvert('base_year', (v) => (v as num).toInt()),
        termCount: $checkedConvert('term_count', (v) => (v as num).toInt()),
      );
      return val;
    }, fieldKeyMap: const {'baseYear': 'base_year', 'termCount': 'term_count'});

Map<String, dynamic> _$TermTemplateToJson(TermTemplate instance) =>
    <String, dynamic>{
      'id': instance.id,
      'name': instance.name,
      'base_year': instance.baseYear,
      'term_count': instance.termCount,
    };
