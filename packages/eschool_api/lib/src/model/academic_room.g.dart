// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_room.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicRoom _$AcademicRoomFromJson(Map<String, dynamic> json) =>
    $checkedCreate('AcademicRoom', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const [
          'name',
          'building',
          'capacity',
          'type',
          'active',
          'id',
          'revision',
        ],
      );
      final val = AcademicRoom(
        name: $checkedConvert('name', (v) => v as String),
        building: $checkedConvert('building', (v) => v as String?),
        capacity: $checkedConvert('capacity', (v) => (v as num?)?.toInt()),
        type: $checkedConvert('type', (v) => v as String),
        active: $checkedConvert('active', (v) => v as bool),
        id: $checkedConvert('id', (v) => (v as num).toInt()),
        revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      );
      return val;
    });

Map<String, dynamic> _$AcademicRoomToJson(AcademicRoom instance) =>
    <String, dynamic>{
      'name': instance.name,
      'building': instance.building,
      'capacity': instance.capacity,
      'type': instance.type,
      'active': instance.active,
      'id': instance.id,
      'revision': instance.revision,
    };
