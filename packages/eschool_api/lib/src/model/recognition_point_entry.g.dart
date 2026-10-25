// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'recognition_point_entry.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

RecognitionPointEntry _$RecognitionPointEntryFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'RecognitionPointEntry',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'house_id',
        'house_name',
        'category_name',
        'points',
        'reason',
        'on_date',
        'public',
        'reverses_id',
      ],
    );
    final val = RecognitionPointEntry(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      houseId: $checkedConvert('house_id', (v) => (v as num).toInt()),
      houseName: $checkedConvert('house_name', (v) => v as String),
      categoryName: $checkedConvert('category_name', (v) => v as String),
      points: $checkedConvert('points', (v) => (v as num).toInt()),
      reason: $checkedConvert('reason', (v) => v as String),
      onDate: $checkedConvert('on_date', (v) => v as String),
      public: $checkedConvert('public', (v) => v as bool),
      reversesId: $checkedConvert('reverses_id', (v) => (v as num?)?.toInt()),
    );
    return val;
  },
  fieldKeyMap: const {
    'houseId': 'house_id',
    'houseName': 'house_name',
    'categoryName': 'category_name',
    'onDate': 'on_date',
    'reversesId': 'reverses_id',
  },
);

Map<String, dynamic> _$RecognitionPointEntryToJson(
  RecognitionPointEntry instance,
) => <String, dynamic>{
  'id': instance.id,
  'house_id': instance.houseId,
  'house_name': instance.houseName,
  'category_name': instance.categoryName,
  'points': instance.points,
  'reason': instance.reason,
  'on_date': instance.onDate,
  'public': instance.public,
  'reverses_id': instance.reversesId,
};
