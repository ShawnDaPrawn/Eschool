// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'term_templates_response_data.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TermTemplatesResponseData _$TermTemplatesResponseDataFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('TermTemplatesResponseData', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['templates']);
  final val = TermTemplatesResponseData(
    templates: $checkedConvert(
      'templates',
      (v) => (v as List<dynamic>)
          .map((e) => TermTemplate.fromJson(e as Map<String, dynamic>))
          .toList(),
    ),
  );
  return val;
});

Map<String, dynamic> _$TermTemplatesResponseDataToJson(
  TermTemplatesResponseData instance,
) => <String, dynamic>{
  'templates': instance.templates.map((e) => e.toJson()).toList(),
};
