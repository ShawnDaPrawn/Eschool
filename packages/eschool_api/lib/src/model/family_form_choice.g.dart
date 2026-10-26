// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'family_form_choice.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FamilyFormChoice _$FamilyFormChoiceFromJson(Map<String, dynamic> json) =>
    $checkedCreate('FamilyFormChoice', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['id', 'name', 'person_id']);
      final val = FamilyFormChoice(
        id: $checkedConvert('id', (v) => (v as num).toInt()),
        name: $checkedConvert('name', (v) => v as String),
        personId: $checkedConvert('person_id', (v) => (v as num).toInt()),
      );
      return val;
    }, fieldKeyMap: const {'personId': 'person_id'});

Map<String, dynamic> _$FamilyFormChoiceToJson(FamilyFormChoice instance) =>
    <String, dynamic>{
      'id': instance.id,
      'name': instance.name,
      'person_id': instance.personId,
    };
