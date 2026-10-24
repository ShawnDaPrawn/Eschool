// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'term_template_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TermTemplateWrite _$TermTemplateWriteFromJson(Map<String, dynamic> json) =>
    $checkedCreate('TermTemplateWrite', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['session_year_id', 'name']);
      final val = TermTemplateWrite(
        sessionYearId: $checkedConvert(
          'session_year_id',
          (v) => (v as num).toInt(),
        ),
        name: $checkedConvert('name', (v) => v as String),
      );
      return val;
    }, fieldKeyMap: const {'sessionYearId': 'session_year_id'});

Map<String, dynamic> _$TermTemplateWriteToJson(TermTemplateWrite instance) =>
    <String, dynamic>{
      'session_year_id': instance.sessionYearId,
      'name': instance.name,
    };
