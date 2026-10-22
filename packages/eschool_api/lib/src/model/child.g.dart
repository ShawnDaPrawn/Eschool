// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'child.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

Child _$ChildFromJson(Map<String, dynamic> json) => $checkedCreate(
  'Child',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'user_id',
        'first_name',
        'last_name',
        'name',
        'image',
        'school',
      ],
    );
    final val = Child(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      userId: $checkedConvert('user_id', (v) => (v as num).toInt()),
      firstName: $checkedConvert('first_name', (v) => v as String),
      lastName: $checkedConvert('last_name', (v) => v as String),
      name: $checkedConvert('name', (v) => v as String),
      image: $checkedConvert('image', (v) => v as String?),
      school: $checkedConvert(
        'school',
        (v) => SchoolSummary.fromJson(v as Map<String, dynamic>),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'userId': 'user_id',
    'firstName': 'first_name',
    'lastName': 'last_name',
  },
);

Map<String, dynamic> _$ChildToJson(Child instance) => <String, dynamic>{
  'id': instance.id,
  'user_id': instance.userId,
  'first_name': instance.firstName,
  'last_name': instance.lastName,
  'name': instance.name,
  'image': instance.image,
  'school': instance.school.toJson(),
};
