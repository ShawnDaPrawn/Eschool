// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'strengths_topic_section.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

StrengthsTopicSection _$StrengthsTopicSectionFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('StrengthsTopicSection', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['available', 'title', 'message', 'items'],
  );
  final val = StrengthsTopicSection(
    available: $checkedConvert('available', (v) => v as bool),
    title: $checkedConvert('title', (v) => v as String),
    message: $checkedConvert('message', (v) => v as String),
    items: $checkedConvert(
      'items',
      (v) => (v as List<dynamic>)
          .map((e) => StrengthsTopic.fromJson(e as Map<String, dynamic>))
          .toList(),
    ),
  );
  return val;
});

Map<String, dynamic> _$StrengthsTopicSectionToJson(
  StrengthsTopicSection instance,
) => <String, dynamic>{
  'available': instance.available,
  'title': instance.title,
  'message': instance.message,
  'items': instance.items.map((e) => e.toJson()).toList(),
};
