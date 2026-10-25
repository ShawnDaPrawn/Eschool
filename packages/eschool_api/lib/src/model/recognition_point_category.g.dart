// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'recognition_point_category.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

RecognitionPointCategory _$RecognitionPointCategoryFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'RecognitionPointCategory',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'name',
        'default_points',
        'minimum_points',
        'maximum_points',
        'active',
        'revision',
      ],
    );
    final val = RecognitionPointCategory(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      name: $checkedConvert('name', (v) => v as String),
      defaultPoints: $checkedConvert(
        'default_points',
        (v) => (v as num).toInt(),
      ),
      minimumPoints: $checkedConvert(
        'minimum_points',
        (v) => (v as num).toInt(),
      ),
      maximumPoints: $checkedConvert(
        'maximum_points',
        (v) => (v as num).toInt(),
      ),
      active: $checkedConvert('active', (v) => v as bool),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
    );
    return val;
  },
  fieldKeyMap: const {
    'defaultPoints': 'default_points',
    'minimumPoints': 'minimum_points',
    'maximumPoints': 'maximum_points',
  },
);

Map<String, dynamic> _$RecognitionPointCategoryToJson(
  RecognitionPointCategory instance,
) => <String, dynamic>{
  'id': instance.id,
  'name': instance.name,
  'default_points': instance.defaultPoints,
  'minimum_points': instance.minimumPoints,
  'maximum_points': instance.maximumPoints,
  'active': instance.active,
  'revision': instance.revision,
};
