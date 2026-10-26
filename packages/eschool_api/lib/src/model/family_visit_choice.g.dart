// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'family_visit_choice.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FamilyVisitChoice _$FamilyVisitChoiceFromJson(Map<String, dynamic> json) =>
    $checkedCreate('FamilyVisitChoice', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['id', 'name']);
      final val = FamilyVisitChoice(
        id: $checkedConvert('id', (v) => (v as num).toInt()),
        name: $checkedConvert('name', (v) => v as String),
      );
      return val;
    });

Map<String, dynamic> _$FamilyVisitChoiceToJson(FamilyVisitChoice instance) =>
    <String, dynamic>{'id': instance.id, 'name': instance.name};
