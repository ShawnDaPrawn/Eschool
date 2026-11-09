// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'strengths_activities_section.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

StrengthsActivitiesSection _$StrengthsActivitiesSectionFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('StrengthsActivitiesSection', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['available', 'title', 'message', 'items', 'skills'],
  );
  final val = StrengthsActivitiesSection(
    available: $checkedConvert('available', (v) => v as bool),
    title: $checkedConvert('title', (v) => v as String),
    message: $checkedConvert('message', (v) => v as String),
    items: $checkedConvert(
      'items',
      (v) => (v as List<dynamic>)
          .map((e) => StrengthsActivity.fromJson(e as Map<String, dynamic>))
          .toList(),
    ),
    skills: $checkedConvert(
      'skills',
      (v) => (v as List<dynamic>)
          .map((e) => StrengthsSkill.fromJson(e as Map<String, dynamic>))
          .toList(),
    ),
  );
  return val;
});

Map<String, dynamic> _$StrengthsActivitiesSectionToJson(
  StrengthsActivitiesSection instance,
) => <String, dynamic>{
  'available': instance.available,
  'title': instance.title,
  'message': instance.message,
  'items': instance.items.map((e) => e.toJson()).toList(),
  'skills': instance.skills.map((e) => e.toJson()).toList(),
};
