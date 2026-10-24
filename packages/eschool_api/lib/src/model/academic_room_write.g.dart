// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_room_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicRoomWrite _$AcademicRoomWriteFromJson(Map<String, dynamic> json) =>
    $checkedCreate('AcademicRoomWrite', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['name', 'type', 'active']);
      final val = AcademicRoomWrite(
        name: $checkedConvert('name', (v) => v as String),
        building: $checkedConvert('building', (v) => v as String?),
        capacity: $checkedConvert('capacity', (v) => (v as num?)?.toInt()),
        type: $checkedConvert('type', (v) => v as String),
        active: $checkedConvert('active', (v) => v as bool),
        revision: $checkedConvert('revision', (v) => (v as num?)?.toInt()),
      );
      return val;
    });

Map<String, dynamic> _$AcademicRoomWriteToJson(AcademicRoomWrite instance) =>
    <String, dynamic>{
      'name': instance.name,
      'building': ?instance.building,
      'capacity': ?instance.capacity,
      'type': instance.type,
      'active': instance.active,
      'revision': ?instance.revision,
    };
