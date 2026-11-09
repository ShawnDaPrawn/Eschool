// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'strengths_reading_section.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

StrengthsReadingSection _$StrengthsReadingSectionFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('StrengthsReadingSection', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['available', 'title', 'message', 'items'],
  );
  final val = StrengthsReadingSection(
    available: $checkedConvert('available', (v) => v as bool),
    title: $checkedConvert('title', (v) => v as String),
    message: $checkedConvert('message', (v) => v as String),
    items: $checkedConvert(
      'items',
      (v) => (v as List<dynamic>).map((e) => e as Object).toList(),
    ),
  );
  return val;
});

Map<String, dynamic> _$StrengthsReadingSectionToJson(
  StrengthsReadingSection instance,
) => <String, dynamic>{
  'available': instance.available,
  'title': instance.title,
  'message': instance.message,
  'items': instance.items,
};
