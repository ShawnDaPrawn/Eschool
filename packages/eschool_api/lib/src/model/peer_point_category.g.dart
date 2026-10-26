// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'peer_point_category.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PeerPointCategory _$PeerPointCategoryFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'PeerPointCategory',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'id',
            'name',
            'minimum_points',
            'maximum_points',
            'default_points',
          ],
        );
        final val = PeerPointCategory(
          id: $checkedConvert('id', (v) => (v as num).toInt()),
          name: $checkedConvert('name', (v) => v as String),
          minimumPoints: $checkedConvert(
            'minimum_points',
            (v) => (v as num).toInt(),
          ),
          maximumPoints: $checkedConvert(
            'maximum_points',
            (v) => (v as num).toInt(),
          ),
          defaultPoints: $checkedConvert(
            'default_points',
            (v) => (v as num).toInt(),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'minimumPoints': 'minimum_points',
        'maximumPoints': 'maximum_points',
        'defaultPoints': 'default_points',
      },
    );

Map<String, dynamic> _$PeerPointCategoryToJson(PeerPointCategory instance) =>
    <String, dynamic>{
      'id': instance.id,
      'name': instance.name,
      'minimum_points': instance.minimumPoints,
      'maximum_points': instance.maximumPoints,
      'default_points': instance.defaultPoints,
    };
