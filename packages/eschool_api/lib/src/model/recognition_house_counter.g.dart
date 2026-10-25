// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'recognition_house_counter.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

RecognitionHouseCounter _$RecognitionHouseCounterFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'RecognitionHouseCounter',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'name',
        'emblem',
        'primary_colour',
        'secondary_colour',
        'active',
        'revision',
        'points',
      ],
    );
    final val = RecognitionHouseCounter(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      name: $checkedConvert('name', (v) => v as String),
      emblem: $checkedConvert('emblem', (v) => v as String),
      primaryColour: $checkedConvert('primary_colour', (v) => v as String),
      secondaryColour: $checkedConvert('secondary_colour', (v) => v as String),
      active: $checkedConvert('active', (v) => v as bool),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      points: $checkedConvert('points', (v) => (v as num).toInt()),
    );
    return val;
  },
  fieldKeyMap: const {
    'primaryColour': 'primary_colour',
    'secondaryColour': 'secondary_colour',
  },
);

Map<String, dynamic> _$RecognitionHouseCounterToJson(
  RecognitionHouseCounter instance,
) => <String, dynamic>{
  'id': instance.id,
  'name': instance.name,
  'emblem': instance.emblem,
  'primary_colour': instance.primaryColour,
  'secondary_colour': instance.secondaryColour,
  'active': instance.active,
  'revision': instance.revision,
  'points': instance.points,
};
