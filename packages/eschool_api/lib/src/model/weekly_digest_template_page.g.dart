// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'weekly_digest_template_page.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WeeklyDigestTemplatePage _$WeeklyDigestTemplatePageFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('WeeklyDigestTemplatePage', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['items', 'starting_templates']);
  final val = WeeklyDigestTemplatePage(
    items: $checkedConvert(
      'items',
      (v) => (v as List<dynamic>)
          .map((e) => WeeklyDigestTemplate.fromJson(e as Map<String, dynamic>))
          .toList(),
    ),
    startingTemplates: $checkedConvert(
      'starting_templates',
      (v) => (v as Map<String, dynamic>).map(
        (k, e) => MapEntry(
          k,
          WeeklyDigestStartingTemplate.fromJson(e as Map<String, dynamic>),
        ),
      ),
    ),
  );
  return val;
}, fieldKeyMap: const {'startingTemplates': 'starting_templates'});

Map<String, dynamic> _$WeeklyDigestTemplatePageToJson(
  WeeklyDigestTemplatePage instance,
) => <String, dynamic>{
  'items': instance.items.map((e) => e.toJson()).toList(),
  'starting_templates': instance.startingTemplates.map(
    (k, e) => MapEntry(k, e.toJson()),
  ),
};
