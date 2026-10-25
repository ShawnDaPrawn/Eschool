// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'secure_card_person.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SecureCardPerson _$SecureCardPersonFromJson(Map<String, dynamic> json) =>
    $checkedCreate('SecureCardPerson', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['id', 'name', 'type']);
      final val = SecureCardPerson(
        id: $checkedConvert('id', (v) => (v as num).toInt()),
        name: $checkedConvert('name', (v) => v as String),
        type: $checkedConvert('type', (v) => v as String),
      );
      return val;
    });

Map<String, dynamic> _$SecureCardPersonToJson(SecureCardPerson instance) =>
    <String, dynamic>{
      'id': instance.id,
      'name': instance.name,
      'type': instance.type,
    };
