// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'context_user.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ContextUser _$ContextUserFromJson(Map<String, dynamic> json) =>
    $checkedCreate('ContextUser', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const [
          'id',
          'first_name',
          'last_name',
          'name',
          'email',
          'image',
          'roles',
        ],
      );
      final val = ContextUser(
        id: $checkedConvert('id', (v) => (v as num).toInt()),
        firstName: $checkedConvert('first_name', (v) => v as String),
        lastName: $checkedConvert('last_name', (v) => v as String),
        name: $checkedConvert('name', (v) => v as String),
        email: $checkedConvert('email', (v) => v as String),
        image: $checkedConvert('image', (v) => v as String?),
        roles: $checkedConvert(
          'roles',
          (v) => (v as List<dynamic>).map((e) => e as String).toList(),
        ),
      );
      return val;
    }, fieldKeyMap: const {'firstName': 'first_name', 'lastName': 'last_name'});

Map<String, dynamic> _$ContextUserToJson(ContextUser instance) =>
    <String, dynamic>{
      'id': instance.id,
      'first_name': instance.firstName,
      'last_name': instance.lastName,
      'name': instance.name,
      'email': instance.email,
      'image': instance.image,
      'roles': instance.roles,
    };
