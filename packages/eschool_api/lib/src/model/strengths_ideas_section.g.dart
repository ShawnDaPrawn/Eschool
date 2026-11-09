// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'strengths_ideas_section.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

StrengthsIdeasSection _$StrengthsIdeasSectionFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('StrengthsIdeasSection', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['available', 'title', 'message', 'items'],
  );
  final val = StrengthsIdeasSection(
    available: $checkedConvert('available', (v) => v as bool),
    title: $checkedConvert('title', (v) => v as String),
    message: $checkedConvert('message', (v) => v as String),
    items: $checkedConvert(
      'items',
      (v) => (v as List<dynamic>)
          .map((e) => StrengthsIdea.fromJson(e as Map<String, dynamic>))
          .toList(),
    ),
  );
  return val;
});

Map<String, dynamic> _$StrengthsIdeasSectionToJson(
  StrengthsIdeasSection instance,
) => <String, dynamic>{
  'available': instance.available,
  'title': instance.title,
  'message': instance.message,
  'items': instance.items.map((e) => e.toJson()).toList(),
};
