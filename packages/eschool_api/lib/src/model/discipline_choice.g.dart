// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'discipline_choice.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DisciplineChoice _$DisciplineChoiceFromJson(Map<String, dynamic> json) =>
    $checkedCreate('DisciplineChoice', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['id', 'name']);
      final val = DisciplineChoice(
        id: $checkedConvert('id', (v) => (v as num).toInt()),
        name: $checkedConvert('name', (v) => v as String),
      );
      return val;
    });

Map<String, dynamic> _$DisciplineChoiceToJson(DisciplineChoice instance) =>
    <String, dynamic>{'id': instance.id, 'name': instance.name};
